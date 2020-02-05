<?php

/**
 * Creates all resources related to the tao font from the icomoon export
 * Antoine Robin <antoine.robin@vesperiagroup.com>
 * based on work of Dieter Raber <dieter@taotesting.com>
 */

namespace oat\taoDevTools\actions;

use common_Logger;
use Exception;
use RuntimeException;
use tao_actions_CommonModule;
use tao_helpers_File;
use ZipArchive;

class FontConversion extends tao_actions_CommonModule
{
    protected const FIELD_ERROR = 'error';
    protected const FIELD_SUCCESS = 'success';

    private $workingDirectory;
    private $temporaryDirectory;
    private $assetsDirectory;
    private $doNotEdit;
    private $currentSelection;
    private $taoCoreExtensionDirectory;

    /**
     * Entry point to the tool
     *
     * @throws Exception
     */
    public function index()
    {
        $this->init();
        $this->setView('fontConversion/view.tpl');
    }

    protected function init()
    {
        $this->temporaryDirectory = tao_helpers_File::createTempDir();
        $this->workingDirectory = str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__));
        $this->taoCoreExtensionDirectory = dirname($this->workingDirectory) . '/tao';
        $this->assetsDirectory = $this->workingDirectory . '/fontConversion/assets';
        $this->currentSelection = $this->assetsDirectory . '/selection.json';

        /** todo: remove files template */
        $this->doNotEdit = file_get_contents($this->assetsDirectory . '/do-not-edit.tpl');

        $writable = [
            $this->taoCoreExtensionDirectory . '/views/css/font/tao/',
            $this->taoCoreExtensionDirectory . '/views/scss/inc/fonts/',
            $this->taoCoreExtensionDirectory . '/views/js/lib/ckeditor/skins/tao/scss/inc/',
            $this->taoCoreExtensionDirectory . '/helpers/',
            $this->assetsDirectory,
        ];

        foreach ($writable as $location ) {
            if (!is_writable($location)) {
                throw new RuntimeException(implode("\n<br>", $writable) . ' must be writable');
            }
        }

        $this->setData('icon-listing', $this->loadIconListing());
    }

    /**
     * Process the font archive
     *
     * @return array|bool
     * @throws Exception
     */
    public function processFontArchive()
    {
        $this->init();

        // upload result is either the path to the zip file or an array with errors
        $uploadResult = $this->uploadArchive();
        if (!empty($uploadResult[self::FIELD_ERROR])) {
            $this->returnJson($uploadResult);
            return false;
        }

        // extract result is either the path to the extracted files or an array with errors
        $extractResult = $this->extractArchive($uploadResult);
        if (!empty($extractResult[self::FIELD_ERROR])) {
            $this->returnJson($extractResult);
            return false;
        }

        // check if the new font contains at least al glyphs from the previous version
        $currentSelection = json_decode(file_get_contents($extractResult . '/selection.json'), false);
        $oldSelection = json_decode(file_get_contents($this->currentSelection), false);
        $integrityCheck = $this->checkIntegrity($currentSelection, $oldSelection);
        if (!empty($integrityCheck[self::FIELD_ERROR])) {
            $this->returnJson($integrityCheck);
            return false;
        }

        // generate tao scss
        $scssGenerationResult = $this->generateTaoScss($extractResult, $currentSelection->icons);
        if (!empty($scssGenerationResult[self::FIELD_ERROR])) {
            $this->returnJson($scssGenerationResult);
            return false;
        }

        $this->generateCkScss(); // return path to the generated file, but not used anywhere

        // php generation result is either the path to the php class or an array with errors
        $phpGenerationResult = $this->generatePhpClass($currentSelection->icons);
        if (!empty($phpGenerationResult[self::FIELD_ERROR])) {
            $this->returnJson($phpGenerationResult);
            return false;
        }

        $distribution = $this->distribute($extractResult);
        if (!empty($distribution[self::FIELD_ERROR])) {
            $this->returnJson($distribution);
            return false;
        }

        chdir($this->taoCoreExtensionDirectory . '/views/build');

        $compilationResult = $this->compileCss();
        if (!empty($compilationResult[self::FIELD_ERROR])) {
            $this->returnJson($compilationResult);
            return false;
        }

        $this->returnJson([self::FIELD_SUCCESS => __('The TAO icon font has been updated')]);

        return true;
    }

    /**
     * Upload the zip archive to a tmp directory
     *
     * @return array|string
     */
    protected function uploadArchive()
    {
        if ($_FILES['content']['error'] !== UPLOAD_ERR_OK) {
            common_Logger::w('File upload failed with error ' . $_FILES['content']['error']);
            switch ($_FILES['content']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = __('Archive size must be lesser than : ') . ini_get('post_max_size');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = __('No file uploaded');
                    break;
                default:
                    $error = __('File upload failed');
                    break;
            }
            return $this->error($error);
        }

        $filePath = $this->temporaryDirectory . '/' . $_FILES['content']['name'];

        if (!move_uploaded_file($_FILES['content']['tmp_name'], $filePath)) {
            return $this->error(__('Unable to move uploaded file'));
        }

        return $filePath;
    }

    /**
     * Unzip archive from icomoon
     *
     * @param $archiveFile
     * @return array|string
     */
    protected function extractArchive($archiveFile)
    {
        $archiveDirectory = dirname($archiveFile);
        $archive = new ZipArchive();
        $archiveHandle = $archive->open($archiveFile);

        if (true !== $archiveHandle) {
            return $this->error(__('Could not open archive'));
        }

        if (!$archive->extractTo($archiveDirectory)) {
            $archive->close();
            return $this->error(__('Could not extract archive'));
        }

        $archive->close();

        return $archiveDirectory;
    }

    /**
     * Checks whether the new font contains at least all glyphs from the previous version
     *
     * @param $currentSelection
     * @param $oldSelection
     * @return bool|array
     */
    protected function checkIntegrity($currentSelection, $oldSelection)
    {
        $metadataExists = property_exists($currentSelection, 'metadata')
            && property_exists($currentSelection->metadata, 'name');

        $prefExists = property_exists($currentSelection, 'preferences')
            && property_exists($currentSelection->preferences, 'fontPref')
            && property_exists($currentSelection->preferences->fontPref, 'metadata')
            && property_exists($currentSelection->preferences->fontPref->metadata, 'fontFamily') ;

        if (
            ($metadataExists && $currentSelection->metadata->name !== 'tao')
            || ($prefExists && $currentSelection->preferences->fontPref->metadata->fontFamily !== 'tao')
            || (!$prefExists && !$metadataExists)
        ) {
            return $this->error(__('You need to change the font name to "tao" in the icomoon preferences'));
        }

        $newSet = $this->dataToGlyphSet($currentSelection);
        $oldSet = $this->dataToGlyphSet($oldSelection);

        return (bool)count(array_diff($oldSet, $newSet))
            ? $this->error(__('Font incomplete! Is the extension in sync width git? Have you removed any glyphs?'))
            : true;
    }

    /**
     * Generate a listing of all glyph names in a font
     *
     * @param $data
     * @return array
     */
    protected function dataToGlyphSet(object $data)
    {
        $glyphs = [];
        foreach ($data->icons as $iconProperties) {
            $glyphs[] = $iconProperties->properties->name;
        }

        return $glyphs;
    }

    /**
     * Generate TAO SCSS
     *
     * @param $archiveDir
     * @param $icons
     * @return array
     */
    protected function generateTaoScss($archiveDir, $icons)
    {
        if (!is_readable($archiveDir . '/style.css')) {
            return $this->error(__('Unable to read the file : ') . $archiveDir . '/style.css');
        }

        $cssContent = file_get_contents($archiveDir . '/style.css');

        $iconCss = [
            'classes' => '',
            'def' => '',
            'vars' => '',
        ];

        // font-face
        $cssContentArr = explode('[class^="icon-"]', $cssContent);
        $iconCss['def'] = str_replace('fonts/tao.', '#{$fontPath}tao/tao.', $cssContentArr[0]) . PHP_EOL;

        // font-family etc.
        $cssContentArr = explode('.icon', $cssContentArr[1]);
        $iconCss['vars'] = str_replace(', [class*=" icon-"]', '@mixin tao-icon-setup', $cssContentArr[0]);

        // the actual css code
        $iconCss['classes'] = '[class^="icon-"], [class*=" icon-"] { @include tao-icon-setup; }' . PHP_EOL;

        // build code for PHP icon class and tao-*.scss files
        foreach ($icons as $iconProperties) {
            $properties = $iconProperties->properties;
            $icon = $properties->name;
            $iconHex = dechex($properties->code);

            // tao-*.scss data
            $iconCss['vars'] .= '@mixin icon-' . $icon . ' { content: "\\' . $iconHex . '"; }' . "\n";
            $iconCss['classes'] .= '.icon-' . $icon . ':before { @include icon-' . $icon . '; }' . "\n";
        }

        // compose and write SCSS files
        $retVal = [];
        foreach ($iconCss as $key => $value) {
            $retVal[$key] = $this->temporaryDirectory . '/_tao-icon-' . $key . '.scss';
            file_put_contents($retVal[$key], $this->doNotEdit . $iconCss[$key]);
        }
        return $retVal;
    }

    /**
     * Generate scss for CK editor
     *
     * @return string
     */
    protected function generateCkScss()
    {
        $ckIni = parse_ini_file($this->assetsDirectory . '/ck-editor-classes.ini');

        // ck toolbar icons
        $cssContent = '@import "inc/bootstrap";' . PHP_EOL;
        $cssContent .= '.cke_button_icon, .cke_button { @include tao-icon-setup;}' . PHP_EOL;

        foreach ($ckIni as $ckIcon => $taoIcon) {
            if (!$taoIcon) {
                continue;
            }
            $cssContent .= sprintf('.%s:before { @include %s; }', $ckIcon, $taoIcon) . PHP_EOL;
        }

        file_put_contents($this->temporaryDirectory . '/_ck-icons.scss', $this->doNotEdit . $cssContent);

        return $this->temporaryDirectory . '/_ck-icons.scss';
    }

    /**
     * Generate PHP icon class
     *
     * @param $iconSet
     * @return array|string
     */
    protected function generatePhpClass($iconSet)
    {
        // todo: replace by https://github.com/nette/php-generator

        $phpClass     = file_get_contents($this->assetsDirectory . '/class.Icon.tpl');
        $phpClassPath = $this->tmpDir . '/class.Icon.php';
        $constants    = '';
        $functions    = '';
        $patterns     = ['{CONSTANTS}', '{FUNCTIONS}', '{DATE}', '{DO_NOT_EDIT}'];

        foreach ($iconSet as $iconProperties) {
            $icon = $iconProperties->properties->name;
            // constants
            $constName = 'CLASS_' . strtoupper(str_replace('-', '_', $icon));
            // functions
            $iconFn = strtolower(trim($icon));
            $iconFn = str_replace(' ', '', ucwords(preg_replace('~[\W_-]+~', ' ', $iconFn)));
            $functions .= '    public static function icon' . $iconFn . '($options=array()){' . "\n"
                . '        return self::buildIcon(self::' . $constName . ', $options);' . "\n" . '    }' . "\n\n";

            $constants .= '    const ' . $constName . ' = \'icon-' . $icon . '\';' . "\n";
        }

        $phpClass = str_replace(
            $patterns,
            [$constants, $functions, date('Y-m-d H:i:s'), $this->doNotEdit],
            $phpClass
        );

        file_put_contents($phpClassPath, $phpClass);

        ob_start();
        system('php -l ' . $phpClassPath);
        $parseResult = ob_get_clean();

        if (false === strpos($parseResult, 'No syntax errors detected')) {
            $parseResult = strtok($parseResult, PHP_EOL);
            return $this->error($parseResult);
        }

        return $phpClassPath;
    }

    /**
     * Distribute generated files to their final destination
     *
     * @param $temporaryDirectory
     *
     * @return array|bool
     */
    protected function distribute($temporaryDirectory)
    {
        // copy fonts
        foreach (glob($temporaryDirectory . '/fonts/tao.*') as $font) {
            if (!copy($font, $this->taoCoreExtensionDirectory . '/views/css/font/tao/' . basename($font))) {
                return $this->error(__('Failed to copy ') . $font);
            }
        }

        // copy icon scss
        foreach (glob($temporaryDirectory . '/_tao-icon-*.scss') as $scss) {
            if (!copy($scss, $this->taoCoreExtensionDirectory . '/views/scss/inc/fonts/' . basename($scss))) {
                return $this->error(__('Failed to copy ') . $scss);
            }
        }

        // copy ck editor styles
        if (!copy($temporaryDirectory . '/_ck-icons.scss', $this->taoCoreExtensionDirectory . '/views/js/lib/ckeditor/skins/tao/scss/inc/_ck-icons.scss')) {
            return $this->error(__('Failed to copy ') . $temporaryDirectory . '/_ck-icons.scss');
        }

        // copy helper class
        if (!copy($temporaryDirectory . '/class.Icon.php', $this->taoCoreExtensionDirectory . '/helpers/class.Icon.php')) {
            return $this->error(__('Failed to copy ') . $temporaryDirectory . '/class.Icon.php');
        }

        // copy selection to assets
        if (!copy($temporaryDirectory . '/selection.json', $this->assetsDirectory . '/selection.json')) {
            return $this->error(__('Failed to copy ') . $temporaryDirectory . '/selection.json');
        }

        return true;
    }

    /**
     * Compile CSS
     *
     * @return array
     */
    protected function compileCss()
    {
        system('grunt taosass', $result);

        return $result !== 0 ? $this->error(__('CSS compilation failed')) : [];
    }

    /**
     * Download current selection to initialize icomoon
     */
    public function downloadCurrentSelection()
    {
        $this->init();
        header('Content-disposition: attachment; filename=selection.json');
        header('Content-type: application/json');
        readfile($this->currentSelection);
        exit();
    }

    /**
     * List existing icons
     *
     * @return mixed
     */
    protected function loadIconListing()
    {
        $json = json_decode(file_get_contents($this->currentSelection), false);

        $icons = array_map(static function ($item) {
            return $item->properties->name;
        }, $json['icons']);

        asort($icons);

        return $icons;
    }

    /**
     * Wrapper for error handling inside class
     *
     * @param string $msg
     *
     * @return array
     */
    private function error($msg = '')
    {
        return [self::FIELD_ERROR => $msg];
    }
}
