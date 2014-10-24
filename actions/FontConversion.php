<?php
/**
 * Creates all resources related to the tao font from the icomoon export
 * Antoine Robin <antoine.robin@vesperiagroup.com>
 * based on work of Dieter Raber <dieter@taotesting.com>
 */

namespace oat\taoDevTools\actions;

use ZipArchive;

class FontConversion extends \tao_actions_CommonModule
{

    private $dir;
    private $distroPaths;
    private $srcPath;
    private $srcPaths;
    private $srcFonts;
    private $taoMaticPath;
    private $doNotEdit;
    private $iconConstants;
    private $iconFunctions;
    private $iconCss;
    private $maxFileSize;
    private $endMarker;
    private $iconValue;
    private $iconJson;

    public function __construct()
    {
        $this->dir         = dirname(__DIR__);
        $this->taoPath     = dirname($this->dir) . '/tao';
        $this->distroPaths = array(
            'font'    => $this->taoPath . '/views/css/font/tao',
            'style'   => $this->taoPath . '/views/scss/inc',
            'ck'      => $this->taoPath . '/views/js/lib/ckeditor/skins/tao/scss/inc',
            'helpers' => $this->taoPath . '/helpers'
        );

        $this->iconConstants = '';
        $this->iconFunctions = '';
        $this->iconCss       = array(
            'classes' => '',
            'def'     => '',
            'vars'    => ''
        );

        $this->taoMaticPath = str_replace(DIRECTORY_SEPARATOR, '/', $this->dir) . '/fontConversion/assets';

        $this->doNotEdit   = file_get_contents($this->taoMaticPath . '/do-not-edit.tpl');
        $this->maxFileSize = ini_get('post_max_size');
        $this->endMarker   = '.generated_icons_end_marker{marker : end;}';
        $this->iconValue   = array();

        $this->iconJson = $this->taoMaticPath . '/selection.json';

        $this->setData('icon-listing', $this->loadIconListing());
    }

    public function index()
    {
        $this->setData('upload_limit', $this->maxFileSize);
        $this->setView('fontConversion/view.tpl');
    }

    /**
     * Verify that the new icon set contains the old icons
     *
     * @param $data - list of new icons
     * @return array the list of missing icons or TRUE
     */
    private function isSelectionCorrect($data)
    {
        $remainingData = json_decode(file_get_contents($this->taoMaticPath . '/dataName.json'));
        $errors        = array_diff($remainingData, $data);
        return (empty($errors)) ? : $errors;

    }

    /**
     * Catch the form submission and upload the file
     */
    public function fileUpload()
    {
        $error = '';
        $conversion = '';

        $copy = true;
        if ($_FILES['content']['error'] !== UPLOAD_ERR_OK) {

            \common_Logger::w('File upload failed with error ' . $_FILES['content']['error']);

            $copy = false;
            switch ($_FILES['content']['error']) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = __('Media size must be lesser than : ') . ($this->maxFileSize / 1048576) . __(' MB');
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = __('File upload failed');
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = __('No file uploaded');
                    break;
            }
        } else {

            if (!isset($_FILES['content']['type'])) {
                $copy = false;
            } elseif (empty($_FILES['content']['type'])) {
                $finfo                     = finfo_open(FILEINFO_MIME_TYPE);
                $_FILES['content']['type'] = finfo_file($finfo, $_FILES['content']['tmp_name']);
            }
            if (!$_FILES['content']['type'] || $_FILES['content']['type'] != 'application/zip') {
                $copy  = false;
                $error = __('Incompatible media type : ' . $_FILES['content']['type']);
            }
            if (!isset($_FILES['content']['size'])) {
                $copy  = false;
                $error = __('Unknown media size');
            } else if ($_FILES['content']['size'] > $this->maxFileSize || !is_int($_FILES['content']['size'])) {
                $copy  = false;
                $error = __('Media size must be lesser than : ') . ($this->maxFileSize / 1048576) . __(' MB');
            }
        }

        if ($copy) {
            $fileName = $_FILES['content']['name'];
            $filePath = $this->dir . '/' . $fileName;
            if (!move_uploaded_file($_FILES['content']['tmp_name'], $filePath)) {
                $error = __('Unable to move uploaded file');
            } else {
                $nameWithoutExtension = basename($fileName, '.zip');
                if ($this->extract($filePath, $nameWithoutExtension)) {
                    unlink($filePath);
                    $conversion = $this->runConversion($nameWithoutExtension);
                }
            }
        }

        if (!!$error) {
            echo json_encode(array('error' => $error));
        } else {
            echo json_encode($conversion);
        }

    }

    /**
     * Unzip archive from icomoon
     *
     * @param $zipFile
     * @return bool
     * @throws \common_exception_FileSystemError
     */
    private function extract($zipFile)
    {
        $zipObj    = new ZipArchive();
        $zipHandle = $zipObj->open($zipFile);
        if (true !== $zipHandle) {
            throw new \common_exception_FileSystemError($zipHandle);
        }

        $extractDir = \tao_helpers_File::createTempDir();
        if (!$zipObj->extractTo($extractDir)) {
            $zipObj->close();
            throw new \common_exception_FileSystemError('Could not extract ' . $zipFile . ' to ' . $extractDir);
        }
        $zipObj->close();
        return $extractDir;
    }

    /**
     * Function that convert the file in $filename to the right shape
     *
     * @param $filename
     * @return array
     */
    public function runConversion($filename)
    {
        //get the name of the src directory
        $this->srcPath = $this->dir . '/' . $filename;
        if (is_dir($this->srcPath . '/fonts')) {
            // init remaining variables
            $this->srcPaths = array(
                'font'  => $this->srcPath . '/fonts',
                'tao'   => $this->srcPath . '/fonts/tao',
                'style' => $this->srcPath
            );

            $this->srcFonts = scandir($this->srcPaths['font']);

            // load font configuration along with defaults
            $data  = json_decode(file_get_contents($this->srcPaths['style'] . '/selection.json', 'r'));
            $prefs = json_decode(file_get_contents($this->taoMaticPath . '/selection.prefs.json', 'r'));

            // if the preferences are different between old and new icons we throw an error
            foreach ($prefs->fontPref as $key => $value) {
                if ($value != $data->preferences->fontPref->$key) {
                    return array('error' => __('Your font is not compatible with the preferences'));
                }
            }

            // get all icons name
            $dataName = array();
            foreach ($data->icons as $iconProperties) {
                $dataName[] = $iconProperties->properties->name;
            }

            $errors = $this->isSelectionCorrect($dataName);
            if (is_array($errors)) {
                return array(
                    'error' => __('Your selection.json file contains errors, missing: ') . implode(
                            ", ",
                            $errors
                        )
                );
            }

            // Write list of data name
            file_put_contents($this->taoMaticPath . '/dataName.json', json_encode($dataName));

            // create directory structure
            foreach ($this->distroPaths as $path) {
                if (!is_dir($path)) {
                    mkdir($path, 0777, true);
                }
            }

            // copy fonts
            foreach ($this->srcFonts as $font) {
                $fontName = $this->srcPaths['font'] . '/' . $font;
                if (!is_dir($fontName) && file_exists($fontName)) {
                    copy($fontName, $this->distroPaths['font'] . '/' . $font);
                }
            }

            // copy selection.json
            copy(
                $this->srcPaths['style'] . '/selection.json',
                $this->taoMaticPath . '/selection.json'
            );

            // read original stylesheet
            if (!$cssContent = file_get_contents($this->srcPaths['style'] . '/style.css')) {
                return array('error' => __('Unable to read the file : ') . $this->srcPaths['style'] . '/style.css');
            }

            // font-face
            $cssContentArr        = explode('[class^="icon-"]', $cssContent);
            $this->iconCss['def'] = str_replace('fonts/tao.', 'fonts/tao/tao.', $cssContentArr[0]) . "\n";
            // font-family etc.
            $cssContentArr         = explode('.icon', $cssContentArr[1]);
            $this->iconCss['vars'] = str_replace(', [class*=" icon-"]', '%tao-icon-setup', $cssContentArr[0]);

            // the actual css code
            $this->iconCss['classes'] = '@import \'inc/tao-icon-vars.scss\';' . "\n"
                . '[class^="icon-"], [class*=" icon-"] { @extend %tao-icon-setup; }' . "\n";


            // build code for PHP icon class and tao-*.scss files
            foreach ($data->icons as $iconProperties) {

                $properties = $iconProperties->properties;
                $icon       = $properties->name;

                // PHP
                $constName = 'CLASS_' . strtoupper(str_replace('-', '_', $icon));
                $iconHex   = dechex($properties->code);

                // icon function name
                $iconFn = strtolower(trim($icon));
                $iconFn = str_replace(' ', '', ucwords(preg_replace('~[\W_-]+~', ' ', $iconFn)));
                $this->iconFunctions .= '    public static function icon' . $iconFn . '($options=array()){' . "\n"
                    . '        return self::buildIcon(self::' . $constName . ', $options);' . "\n" . '    }' . "\n\n";

                $this->iconConstants .= '    const ' . $constName . ' = \'icon-' . $icon . '\';' . "\n";

                // tao-*.scss data
                $this->iconCss['vars'] .= '%icon-' . $icon . ' { content: "' . $iconHex . '"; }' . "\n";
                $this->iconCss['classes'] .= '.icon-' . $icon . ':before { @extend %icon-' . $icon . '; }' . "\n";
                $this->iconValue[$icon] = $iconHex;
            }
            $this->iconCss['classes'] .= $this->endMarker;


            // update configuration
            $data->metadata->name = 'tao';
            $data->preferences    = $prefs;

            // compose and write SCSS files
            foreach ($this->iconCss as $key => $value) {
                $handler = fopen($this->distroPaths['style'] . '/_tao-icon-' . $key . '.scss', 'w');

                if (!$handler) {
                    return array(
                        'error' => __(
                                'Unable to open the file : '
                            ) . $this->srcPaths['style'] . '/selection.json'
                    );
                }
                fwrite($handler, $this->doNotEdit . $this->iconCss[$key]);
                fclose($handler);
            }

            // write the tao-main-style.css with iconCss
            $this->parseCss($this->taoPath . '/views/css/tao-main-style.css');

            // write PHP icon class
            $phpContent = file_get_contents($this->taoMaticPath . '/class.Icon.tpl');

            $handler = fopen($this->distroPaths['helpers'] . '/class.Icon.php', 'w');

            if (!$handler) {
                return array(
                    'error' => __(
                            'Unable to open the file : '
                        ) . $this->distroPaths['helpers'] . '/class.Icon.php'
                );
            }
            $phpContent = str_replace('{CONSTANTS}', $this->iconConstants, $phpContent);
            $phpContent = str_replace('{FUNCTIONS}', $this->iconFunctions, $phpContent);
            $phpContent = str_replace('{DATE}', time('Y-m-d H:M'), $phpContent);
            $phpContent = str_replace('{DO_NOT_EDIT}', $this->doNotEdit, $phpContent);
            fwrite($handler, $phpContent);
            fclose($handler);

            // check validity of the PHP icon class

            try{
                $iconClass = new \icon($this->taoPath);
                $iconClass->test();
            } catch(\Exception $e){
                \common_Logger::e($e->getMessage());

                if ($iconClass->classExists()) {
                    rename($iconClass->getIconClass(), __DIR__ . '/errors/' . date('Y-m-d') . '-broken.class.Icon.php');
                }
            }

            // ck toolbar icons
            $cssContent = '@import "inc/bootstrap";' . "\n";
            $cssContent .= '.cke_button_icon, .cke_button { @extend %tao-icon-setup;}' . "\n";
            $ckEditor      = file_get_contents($this->taoMaticPath . '/ck-editor-classes.ini');
            $ckEditorArray = explode("\n", str_replace(" ", "", $ckEditor));
            foreach ($ckEditorArray as $value) {
                $pos = strpos($value, '=');
                if (!$pos) {
                    continue;
                }
                $ckIcon  = substr($value, 0, $pos);
                $taoIcon = substr($value, $pos + 1);
                if ($taoIcon == '') {
                    continue;
                }
                $taoIcon = str_replace("\r", "", $taoIcon);
                $cssContent .= '.' . $ckIcon . ':before { @extend %' . $taoIcon . ';}' . "\n";
            }
            $handler = fopen($this->distroPaths['ck'] . '/_ck-icons.scss', 'w');
            if (!$handler) {
                return array(
                    'error' => __(
                            'Unable to read the file : '
                        ) . $this->distroPaths['ck'] . '/_ck-icons.scss'
                );
            }
            fwrite($handler, $cssContent);
            fclose($handler);

            return array('success' => __('Your font is now the new font of TAO'));

        } else {
            return array('error' => $this->srcPath . __(' is not a valid directory'));
        }


    }


    /**
     * Parse a css file (tao-main-style) and update classes
     *
     * @param $filename
     */
    private function parseCss($filename)
    {
        $cssContent = file_get_contents($filename);

        $cssLines    = explode("}\n", $cssContent);
        $replacement = '';
        $matches     = array();
        $allClasses  = array();

        // Parse css file line by line
        foreach ($cssLines as $line) {
            $line = ltrim($line);
            $line .= '}';

            // if the line is like .icon-email:before { content: "\141"; } we update the value
            $pattern = '#((\.icon-([\w-]+))\b([^{]+)){\s*content ?: ?(\'|")([^(\'|")]+)(\'|");([^}]+)}#';
            if (preg_match($pattern, $line, $matches) !== 0) {
                $selector  = trim(preg_replace('~\s+~', ' ', trim($matches[1])));
                $classes   = array();
                $classes[] = $matches[3];

                $allClasses = array_merge($allClasses, $classes);
                // write the new classes
                $replacement .= $selector . '{content:"\\' . $this->iconValue[$matches[3]] . '";}';
                $cssContent = str_replace($line, $replacement, $cssContent) . "\n";

            } // write the new icons at the end
            else if (strpos($line, $this->endMarker) !== false) {
                $newClasses = array_diff(array_keys($this->iconValue), $allClasses);
                foreach ($newClasses as $icon) {
                    $replacement .= '.icon-' . $icon . ':before{content:"\\' . $this->iconValue[$icon] . '"; }' . "\n\n";
                }
                $replacement .= $this->endMarker;
                $cssContent = str_replace($line, $replacement, $cssContent);
            }

        }
        file_put_contents($filename, $cssContent);


    }

    public function downloadCurrentSelection()
    {
        header('Content-disposition: attachment; filename=selection.json');
        header('Content-type: application/json');
        echo(file_get_contents($this->taoMaticPath . '/selection.json'));
    }


    /**
     * Sort existing icons by name
     *
     * @param $a
     * @param $b
     * @return bool
     */
    protected function sortIconListing($a, $b)
    {
        return $a->properties->name > $b->properties->name;
    }

    /**
     * List existing icons
     *
     * @return mixed
     */
    protected function loadIconListing()
    {
        $icons = json_decode(file_get_contents($this->iconJson));
        $icons = $icons->icons;
        usort($icons, array($this, 'sortIconListing'));
        return $icons;
    }


}