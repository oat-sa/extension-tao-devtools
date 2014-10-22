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
    private $endMarker;
    private $iconValue;

    public function __construct()
    {
        $this->dir = dirname(__DIR__);
        $this->taoPath = dirname($this->dir) . '/tao';
        $this->distroPaths = array(
            'font'  =>  $this->taoPath . '/views/css/font/tao',
            'style'  =>  $this->taoPath . '/views/scss/inc',
            'ck'  =>  $this->taoPath . '/views/js/lib/ckeditor/skins/tao/scss/inc',
            'helpers'  =>  $this->taoPath . '/helpers'
        );

        $this->styleGuidePath = $this->dir . '/styleguide/wp-content/themes/twentytwelve';

        $this->iconConstants = '';
        $this->iconFunctions = '';
        $this->iconCss = array(
            'classes'   =>  '',
            'def'       =>  '',
            'vars'      =>  ''
        );

        $this->taoMaticPath = str_replace(DIRECTORY_SEPARATOR, '/', $this->dir) . '/config';
        include($this->taoMaticPath . '/iconTest.php');

        // load 'do not edit' warning
        if(!$this->doNotEdit = file_get_contents($this->taoMaticPath . '/do-not-edit.tpl')){
            throw new \Exception('Unable to read the file : ' . $this->taoMaticPath . '/do-not-edit.tpl');
        }

        $this->maxFileSize = 8388608;
        $this->endMarker = '.generated_icons_end_marker{marker : end;}';
        $this->iconValue = array();
    }

    public function index(){
        $lastModified = filemtime($this->taoMaticPath . '/selection.json');
        $timeSinceLastModified = time() - $lastModified;
        if($timeSinceLastModified > 2 * 3600){
            $warning = __('You are about to change the TAO icon set.')."<br>";
            $warning .= __('Before you do this please make sure that the directory');
            $warning .= " <code>".$this->taoMaticPath . '/selection.json </code>';
            $warning .= __('is in sync with the corresponding git repository.');
            $this->setData('warning', $warning);
        }

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
        $remainingData  = json_decode(file_get_contents($this->taoMaticPath . '/dataName.json'));
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
                    if($this->extract($this->dir.DIRECTORY_SEPARATOR.$fileName, basename($fileName,'.zip'))){
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
    private function extract($zipFile, $filename, $subfolder = '') {
        $zip = new ZipArchive();
        if ($zip->open($zipFile) === true) {
            $destination = $this->dir.$subfolder.'/'.$filename;
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

//            if(!$this->generateDemo($dataName)){
//                return array('error' => __('Unable to generate the demo file'));
//            }

            $errors = $this->isSelectionCorrect($dataName);
            if(is_array($errors)){
                return array('error' => __('Your selection.json file contains error, missing : ') . implode(", ",$errors));
            }

            // Write list of data name
            file_put_contents($this->taoMaticPath.'/dataName.json',json_encode($dataName));

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

            // copy selection.json
            copy($this->srcPaths['style'] . '/selection.json', $this->taoMaticPath.DIRECTORY_SEPARATOR.'selection.json');

            // read original stylesheet
            if(!$cssContent = file_get_contents($this->srcPaths['style'] . '/style.css')){
                return array('error' => __('Unable to read the file : ') . $this->srcPaths['style'] . '/style.css');
            }

            // font-face
            $cssContentArr = explode('[class^="icon-"]',$cssContent);
            $this->iconCss['def'] = str_replace('fonts/tao.', 'fonts/tao/tao.',$cssContentArr[0])."\n";
            // font-family etc.
            $cssContentArr = explode('.icon',$cssContentArr[1]);
            $this->iconCss['vars'] = str_replace(', [class*=" icon-"]','%tao-icon-setup',$cssContentArr[0]);

            // the actual css code
            $this->iconCss['classes'] = '@import \'inc/tao-icon-vars.scss\';'."\n".'[class^="icon-"], [class*=" icon-"] { @extend %tao-icon-setup; }'."\n";


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
                $this->iconValue[$icon] = $iconHex;
            }
            $this->iconCss['classes'] .= $this->endMarker;


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

            // write the tao-main-style.css with iconCss
            $this->parseCss($this->taoPath.DIRECTORY_SEPARATOR.'views/css/tao-main-style.css');

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

            try {
                $iconClass = new \icon($this->taoPath);
                $iconClass->test();
            }
            catch (\Exception $e) {
                print $e -> getMessage();
                file_put_contents(__DIR__ . '/errors/' . date('Y-m-d') . '-error.log', date('Y-m-d H:i:s') . ' - ' . $e -> getMessage() . PHP_EOL, FILE_APPEND);
                if($iconClass->classExists()) {
                    rename($iconClass->getIconClass(), __DIR__ . '/errors/' . date('Y-m-d') . '-broken.class.Icon.php');
                }
            }

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

            $this->delTree($this->srcPath);
            return array('success' => __('Your font is now the new font of TAO'));

        }
        else{
            $this->delTree($this->srcPath);
            return array('error' => $this->srcPath . __(' is not a valid directory'));
        }


    }


    /**
     * Parse a css file (tao-main-style) and update classes
     * @param $filename
     */
    private function parseCss($filename){
        $cssContent = file_get_contents($filename);

        $cssLines = explode("}\n", $cssContent);
        $replacement = '';
        $matches = array();
        $allClasses = array();

        // Parse css file line by line
        foreach($cssLines as $line){
            $line .= '}';
            if(strpos($line, "\n") == 0){
                $line = substr($line, 1);

            }
            // if the line is like .icon-email:before { content: "\141"; } we update the value
            $pattern = '#((\.icon-([\w-]+))\b([^{]+)){\s*content ?: ?(\'|")([^(\'|")]+)(\'|");([^}]+)}#';
            if(preg_match($pattern, $line, $matches) !== 0){
                $selector = trim(preg_replace('~\s+~', ' ', trim($matches[1])));
                $classes = array();
                $classes[] = $matches[3];

                $allClasses = array_merge($allClasses, $classes);
                // write the new classes
                $replacement = $selector . '{content:"\\' . $this->iconValue[$matches[3]] .'";}';
                $cssContent = str_replace($line, $replacement, $cssContent)."\n";

            }
            // write the new icons at the end
            else if(strpos($line, $this->endMarker) !== FALSE){
                $replacement = '';
                $newClasses = array_diff(array_keys($this->iconValue), $allClasses);
                foreach($newClasses as $icon){
                    $replacement .= '.icon-'.$icon.':before{content:"\\'.$this->iconValue[$icon].'"; }'."\n\n";
                }
                $replacement .= $this->endMarker;
                $cssContent = str_replace($line, $replacement, $cssContent);
            }

        }
        file_put_contents($filename, $cssContent);


    }

    public function downloadCurrentSelection(){
        header('Content-disposition: attachment; filename=selection.json');
        header('Content-type: application/json');
        if(file_exists($this->taoMaticPath.DIRECTORY_SEPARATOR.'selection.json')){
            echo(file_get_contents($this->taoMaticPath.'/selection.json'));
        }
        else{
            echo(file_get_contents($this->taoMaticPath . '/selection.prefs.json'));
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

    public function delTree($dir) {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }


}

?>
