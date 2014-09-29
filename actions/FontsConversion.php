<?php
/**
 * Creates all resources related to the tao font from the icomoon export
 * Antoine Robin <antoine.robin@vesperiagroup.com>
 * based on work of Dieter Raber <dieter@taotesting.com>
 */

namespace oat\taoDevTools\actions;

use ZipArchive;

class FontsConversion extends  \tao_actions_CommonModule{

    private $dir;
    private $distroPath;
    private $distroPaths;
    private $srcPath;
    private $srcPaths;
    private $styleGuidePath;
    private $srcFonts;
    private $taoMaticPath;
    private $doNotEdit;
    private $iconConstants;
    private $iconFunctions;
    private $iconCss;
    private $maxFileSize;

    public function __construct()
    {
        $this->dir = dirname(dirname(__FILE__));
        $this->distroPath = $this->dir . '/tao-distro';
        $this->distroPaths = array(
            'font'  =>  $this->distroPath . '/views/css/font/tao',
            'style'  =>  $this->distroPath . '/views/scss/inc',
            'ck'  =>  $this->distroPath . '/views/js/lib/ckeditor/skins/tao/scss/inc',
            'helpers'  =>  $this->distroPath . '/helpers'
        );

        $this->styleGuidePath = $this->dir . '/styleguide/wp-content/themes/twentytwelve';

        $this->iconConstants = '';
        $this->iconFunctions = '';
        $this->iconCss = array(
            'classes'   =>  '',
            'def'       =>  '',
            'vars'      =>  ''
        );

        $this->taoMaticPath = $this->dir . '/config';

        // load 'do not edit' warning
        if(!$this->doNotEdit = file_get_contents($this->taoMaticPath . '/do-not-edit.tpl')){
            throw new \Exception('Unable to read the file : ' . $this->taoMaticPath . '/do-not-edit.tpl');
        }

        $this->maxFileSize = 8388608;
    }

    public function index(){
        $this->setData('file_upload', true);
        $this->setData('upload_limit', $this->maxFileSize);
        $this->setView('fontsConversion/view.tpl');
    }

    /**
     * convert - and _ string to camelCase
     * my-mega_string => myMegaString
     */
    private function camelize($word){
        return preg_replace('/(^|_|-| )(.)/e', 'strtoupper("\\2")', $word);
    }

    /**
     * Verify that the new icon set contains the old icons
     * @param $data the list of new icons
     * @return array the list of missing icons or TRUE
     */
    private function isSelectionCorrect($data){
        $remainingData  = json_decode(file_get_contents($this->taoMaticPath . '/selection.json'));
        $errors = array_diff($remainingData, $data);
        return (empty($errors))?:$errors;

    }

    /**
     * Function that catch the form submit and upload the file
     */
    public function fileUpload(){
        $error = '';
        if(is_array($_FILES['content'])){

            $copy = true;
            if($_FILES['content']['error'] !== UPLOAD_ERR_OK){

                \common_Logger::w('fileUpload failed with Error '.$_FILES['content']['error']);

                $copy = false;
                switch($_FILES['content']['error']){
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        $error = __('media size must be less than : ').($this->maxFileSize / 1048576).__(' MB').'\.';
                        break;
                    case UPLOAD_ERR_PARTIAL:
                        $error = __('file upload failed');
                        break;
                    case UPLOAD_ERR_NO_FILE:
                        $error = __('no file uploaded');
                        break;
                }
            }else{

                if(!isset($_FILES['content']['type'])){
                    $copy = false;
                }elseif(empty($_FILES['content']['type'])){
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $_FILES['content']['type'] = finfo_file($finfo, $_FILES['content']['tmp_name']);
                }
                if(!$_FILES['content']['type'] || $_FILES['content']['type'] != 'application/zip'){
                    $copy = false;
                    $error = __('incompatible media type : '.$_FILES['content']['type']);
                }
                if(!isset($_FILES['content']['size'])){
                    $copy = false;
                    $error = __('unknow media size');
                }else if($_FILES['content']['size'] > $this->maxFileSize || !is_int($_FILES['content']['size'])){
                    $copy = false;
                    $error = __('media size must be less than : ').($this->maxFileSize / 1048576).__(' MB').'\.';
                }
            }

            if($copy){
                $fileName = $_FILES['content']['name'];
                if(!move_uploaded_file($_FILES['content']['tmp_name'], $this->dir.DIRECTORY_SEPARATOR.$fileName)){
                    $error = __('unable to move uploaded file');
                }
                else{
                    if($this->extract($this->dir.DIRECTORY_SEPARATOR.$fileName)){
                        unlink($this->dir.DIRECTORY_SEPARATOR.$fileName);
                        $conversion = $this->runConversion(basename($fileName,'.zip'));
                    }
                }
            }
        }else{
            \common_Logger::w('file upload information missing, probably file > upload limit in php.ini');

            $error = __('media size must be less than : ').($this->maxFileSize / 1048576).__(' MB').'\.';
        }
        if($error != ''){
            echo json_encode(array('error' => $error));
        }
        else{
            echo json_encode($conversion);
        }

    }

    /**
     * Allow the fileUpload method to unzip the file
     * @param $zipFile
     * @param string $subfolder
     * @return bool
     * @throws \common_exception_FileSystemError
     */
    private function extract($zipFile, $subfolder = '') {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            $destination = $this->dir.$subfolder;
            if(!$zip->extractTo($destination)){
                $zip->close();
                throw new \common_exception_FileSystemError('Could not extract '.$zipFile.' to '.$destination);
            }
            $zip->close();
            return true;
        } else {
            throw new \common_exception_FileSystemError('Could not open '.$zipFile);
        }
    }

    /**
     * Generate the demo file for the user style guide
     * @param $dataName
     * @return bool
     */
    private function generateDemo($dataName) {
        $demoTpl = $this->taoMaticPath. DIRECTORY_SEPARATOR . 'demo.tpl';
        $demoContent = file_get_contents($demoTpl);
        asort($dataName);

        $icomoonDemo = '<div class="clearfix mhl ptl">';
        foreach($dataName as $data){
            $icomoonDemo .= str_replace('{ICON}', $data, $demoContent);
        }
        $icomoonDemo .= '</div>';
        if (!is_dir($this->styleGuidePath)){
            mkdir($this->styleGuidePath, 0777, true);
        }
        if(file_put_contents($this->styleGuidePath.DIRECTORY_SEPARATOR.'icons.html',$icomoonDemo) == false){
            return false;
        }

        return true;
    }

    /**
     * Function that convert the file in $filename to the right shape
     * @param $filename
     * @return array
     */
    public function runConversion($filename){
        //get the name of the src directory
        $this->srcPath = $this->dir . DIRECTORY_SEPARATOR . $filename;
        if(is_dir($this->srcPath.DIRECTORY_SEPARATOR.'fonts')){

            // init remaining variables
            $this->srcPaths = array(
                'font'  =>  $this->srcPath . '/fonts',
                'tao'   =>  $this->srcPath . '/fonts/tao',
                'style' =>  $this->srcPath
            );

            $this->srcFonts = scandir($this->srcPaths['font']);

            // load font configuration along with defaults
            $data  = json_decode(file_get_contents($this->srcPaths['style'] . '/selection.json', 'r'));
            $prefs  = json_decode(file_get_contents($this->taoMaticPath . '/selection.prefs.json', 'r'));

            // if the preferences are different between old and new icons we throw an error
            foreach($prefs->fontPref as $key => $value){
                if($value != $data->preferences->fontPref->$key){
                    return array('error' => __('Your font is not compatible with the preferences'));
                }
            }


            // get all icons name
            $dataName = array();
            foreach($data->icons as $iconProperties){
                $dataName[] = $iconProperties->properties->name;
            }

            if(!$this->generateDemo($dataName)){
                return array('error' => __('Unable to generate the demo file'));
            }

            $errors = $this->isSelectionCorrect($dataName);
            if(is_array($errors)){
                return array('error' => __('Your selection.json file contains error, missing : ') . implode(", ",$errors));
            }

            // Write list of data name
            file_put_contents($this->taoMaticPath.'/selection.json',json_encode($dataName));

            // create directory structure
            foreach ($this->distroPaths as $key => $path){
                if (!is_dir($path)){
                    mkdir($path, 0777, true);
                }
            }

            // copy fonts
            foreach ($this->srcFonts as $font){
                $fontName = $this->srcPaths['font'] . '/' .$font;
                if(!is_dir($fontName) && file_exists($fontName)){
                    copy($fontName, $this->distroPaths['font'] . '/' . $font);
                }
            }

            // copy selection.json and tao-main-style.css
            copy(dirname($this->dir) . '/tao/views/css/tao-main-style.css', $this->distroPath.DIRECTORY_SEPARATOR.'tao-main-style.css');
            copy($this->srcPaths['style'] . '/selection.json', $this->distroPath.DIRECTORY_SEPARATOR.'selection.json');

            // read original stylesheet
            if(!$cssContent = file_get_contents($this->srcPaths['style'] . '/style.css')){
                return array('error' => __('Unable to read the file : ') . $this->srcPaths['style'] . '/style.css');
            }

            // font-face
            $cssContentArr = explode('[class^="icon-"]',$cssContent);
            $this->iconCss['def'] = str_replace('fonts/tao.', 'fonts/tao/tao.',$cssContentArr[0]).'\n';
            // font-family etc.
            $cssContentArr = explode('.icon',$cssContentArr[1]);
            $this->iconCss['vars'] = str_replace(', [class*=" icon-"]','%tao-icon-setup',$cssContentArr[0]);

            // the actual css code
            $this->iconCss['classes'] = '@import \'inc/tao-icon-vars.scss\';\n[class^="icon-"], [class*=" icon-"] { @extend %tao-icon-setup; }\n';


            // build code for PHP icon class and tao-*.scss files
            foreach($data->icons as $iconProperties){

                $properties = $iconProperties->properties;
                $icon = $properties->name;

                // PHP
                $constName = 'CLASS_' . strtoupper(str_replace('-','_',$icon));
                $iconHex   = dechex($properties->code);

                $this->iconConstants .= '    const ' . $constName . ' = \'icon-' . $icon . '\';'."\n";
                $this->iconFunctions .= '    public static function ' . $this->camelize('icon-' . $icon) . '($options=array()){'."\n".'        return self::buildIcon(self::' . $constName . ', $options);'."\n".'    }'."\n\n";

                // tao-*.scss data
                $this->iconCss['vars']    .= '%icon-' . $icon . ' { content: "' . $iconHex . '"; }'."\n";
                $this->iconCss['classes'] .= '.icon-' . $icon . ':before { @extend %icon-' . $icon . '; }'."\n";
            }


            // update configuration
            $data->metadata->name = 'tao';
            $data->preferences      = $prefs;

            // compose and write SCSS files
            foreach ($this->iconCss as $key => $value){
                $handler = fopen($this->distroPaths['style'] . '/_tao-icon-' . $key . '.scss', 'w');

                if(!$handler){
                    return array('error' => __('Unable to open the file : ') . $this->srcPaths['style'] . '/selection.json');
                }
                fwrite($handler, $this->doNotEdit . $this->iconCss[$key]);
                fclose($handler);
            }

            // write PHP icon class
            $phpContent = file_get_contents($this->taoMaticPath . '/class.Icon.tpl');

            $handler = fopen($this->distroPaths['helpers'] . '/class.Icon.php', 'w');

            if(!$handler){
                return array('error' => __('Unable to open the file : ') . $this->distroPaths['helpers'] . '/class.Icon.php');
            }
            $phpContent = str_replace('{CONSTANTS}', $this->iconConstants, $phpContent);
            $phpContent = str_replace('{FUNCTIONS}', $this->iconFunctions, $phpContent);
            $phpContent = str_replace('{DATE}', time('Y-m-d H:M'), $phpContent);
            $phpContent = str_replace('{DO_NOT_EDIT}', $this->doNotEdit, $phpContent);
            fwrite($handler, $phpContent);
            fclose($handler);

            // check validity of the PHP icon class
            include($this->taoMaticPath . '/iconTest.php');

            // ck toolbar icons
            $cssContent  = '@import "inc/bootstrap";'."\n";
            $cssContent .= '.cke_button_icon, .cke_button { @extend %tao-icon-setup;}'."\n";
            $ckEditor = file_get_contents($this->taoMaticPath . '/ck-editor-classes.ini');
            $ckEditorArray = explode("\n",str_replace(" ","",$ckEditor));
            foreach($ckEditorArray as $value){
                $pos = strpos($value, '=');
                if(!$pos){
                    continue;
                }
                $ckIcon = substr($value,0, $pos);
                $taoIcon = substr($value,$pos + 1);
                if($taoIcon == ''){
                    continue;
                }
                $taoIcon = str_replace("\r","",$taoIcon);
                $cssContent .= '.' .$ckIcon . ':before { @extend %' . $taoIcon . ';}'."\n";
            }
            $handler = fopen($this->distroPaths['ck'] . '/_ck-icons.scss', 'w');
            if(!$handler){
                return array('error' => __('Unable to read the file : ') . $this->distroPaths['ck'] . '/_ck-icons.scss');
            }
            fwrite($handler,$cssContent);
            fclose($handler);

            $listing = $this->ListIn($this->distroPath);
            $readmeContent = file_get_contents($this->taoMaticPath . '/readme.md');
            $readmeContent = str_replace('{LISTING}', $listing, $readmeContent);


            return array('result' => $readmeContent);

        }
        else{
            return array('error' => $this->srcPath . __(' is not a valid directory'));
        }


    }

    /**
     * List all file of a directory
     * @param $dir
     * @param string $prefix
     * @return string
     */
    private function ListIn($dir, $prefix = '') {
        $indent = sizeof(explode("/", $prefix)) - 1;
        $dir = rtrim($dir, '\\/');
        $result = '';

        $indentation = '';
        for($i=0; $i<$indent;$i++){
            $indentation .= "   ";
        }

        $h = opendir($dir);
        while (($f = readdir($h)) !== false) {
            if ($f !== '.' and $f !== '..') {
                if (is_dir("$dir/$f")) {
                    $result .= $indentation.$prefix.$f."\n";
                    $result .= $this->ListIn("$dir/$f", "$prefix$f/");
                } else {
                    $result .= $indentation.$prefix.$f."\n";
                }
            }
        }
        closedir($h);

        return $result;
    }


}

?>
