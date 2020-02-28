<?php
/**
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; under version 2
 * of the License (non-upgradable).
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 *
 * Copyright (c) 2014-2020 (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
 */

namespace oat\taoDevTools\actions;

use common_Logger;
use Exception;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;
use oat\tao\model\iconBuilder\IconBuilderTrait;
use RuntimeException;
use tao_actions_CommonModule;
use tao_helpers_File;
use ZipArchive;

/**
 * Class FontConversion creates all resources related to the tao font from the icomoon export
 * Antoine Robin <antoine.robin@vesperiagroup.com>
 * based on work of Dieter Raber <dieter@taotesting.com>
 * Adjusted by Ivan Klimchuk <ivan@taotesting.com>
 *
 * @package oat\taoDevTools\actions
 */
class FontConversion extends tao_actions_CommonModule
{
    protected const FIELD_ERROR = 'error';
    protected const FIELD_SUCCESS = 'success';

    protected const DO_NOT_EDIT_TEXT = '/* Do not edit */';

    private $temporaryDirectory;
    private $assetsDirectory;
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
        $workingDirectory = str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__));

        $this->temporaryDirectory = tao_helpers_File::createTempDir();
        $this->taoCoreExtensionDirectory = dirname($workingDirectory) . '/tao';
        $this->assetsDirectory = $workingDirectory . '/fontConversion/assets';
        $this->currentSelection = $this->assetsDirectory . '/selection.json';

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

        if (!empty(array_diff($oldSet, $newSet))) {
            return $this->error(__('Font incomplete! Is the extension in sync with git? Have you removed any glyphs?'));
        }

        return true;
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
            file_put_contents($retVal[$key], self::DO_NOT_EDIT_TEXT . $iconCss[$key]);
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

        file_put_contents($this->temporaryDirectory . '/_ck-icons.scss', self::DO_NOT_EDIT_TEXT . $cssContent);

        return $this->temporaryDirectory . '/_ck-icons.scss';
    }

    /**
     * Generate PHP icon class
     *
     * @param $iconSet
     * @return array|string
     */
    protected function generatePhpClass(array $iconSet = [])
    {
        $license = <<<LICENSE
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; under version 2
of the License (non-upgradable).

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright (c) 2014-%s (original work) Open Assessment Technologies SA (under the project TAO-PRODUCT);
LICENSE;

        $file = new PhpFile();
        $file->addComment(sprintf($license, date('Y')));
        $file->addUse(IconBuilderTrait::class);

        $class = $file->addClass('tao_helpers_Icon');

        $phpDocProperties = [
            'access' => 'public',
            'author' => 'Dieter Raber, <dieter@taotesting.com>',
            'date' => date('Y-m-d H:i:s'),
            'package' => 'tao',
        ];

        array_walk($phpDocProperties, static function (&$item, $key) {
            $item = sprintf("@%s\t\t%s", $key, $item);
        });

        $class->setComment(
            sprintf("Icon helper for tao – helpers/class.Icon.php\n\nPLEASE, DO NOT EDIT THIS CLASS. IT GENERATED AUTOMATICALLY\n\n%s", implode(PHP_EOL, $phpDocProperties))
        );

        $class->addTrait('IconBuilderTrait');

        foreach ($iconSet as $icon) {
            $iconName = $icon->properties->name;

            // adding class constants
            $constantName = sprintf('CLASS_%s', strtoupper(str_replace('-', '_', $iconName)));
            $constantValue = sprintf('icon-%s', $iconName);

            $class->addConstant($constantName, $constantValue)->setPublic();

            // adding methods
            $methodName = 'icon' . str_replace(' ', '', ucwords(preg_replace('~[\W_-]+~', ' ', $iconName)));

            $method = $class->addMethod($methodName)->setStatic()->setPublic();

            $method->addParameter('options')->setType('array')->setDefaultValue([]);
            $method->addBody('return self::buildIcon(self::?, $options);', [$constantName]);
        }

        $phpClassPath = $this->temporaryDirectory . '/class.Icon.php';

        file_put_contents($phpClassPath, (new PsrPrinter())->printFile($file));

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
        }, $json->icons);

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
