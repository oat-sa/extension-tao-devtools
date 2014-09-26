<?php
/**
 * Creates all resources related to the tao font from the icomoon export
 * Antoine Robin <antoine.robin@vesperiagroup.com>
 * based on work of Dieter Raber <dieter@taotesting.com>
 */

namespace oat\taoDevTools\actions;

class FontsConversion extends  \tao_actions_CommonModule{

    private $dir;
    private $distroPath;
    private $distroPaths;
    private $srcPath;
    private $srcPaths;
    private $srcFonts;
    private $taoMaticPath;
    private $doNotEdit;
    private $iconConstants;
    private $iconFunctions;
    private $iconCss;

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

    }

    /**
     * convert - and _ string to camelCase
     * my-mega_string => myMegaString
     */
    private function camelize($word){
        return preg_replace('/(^|_|-| )(.)/e', 'strtoupper("\\2")', $word);
    }

    private function isSelectionCorrect($data){
        $remainingData  = json_decode(file_get_contents($this->taoMaticPath . '/selection.json'));
        $errors = array_diff($remainingData, $data);
        return (empty($errors))?:$errors;

    }

    public function index(){
        $this->setView('fontsConversion/view.tpl');
    }

    public function runConversion(){
        //get the name of the src directory
        $this->srcPath = $this->dir . DIRECTORY_SEPARATOR .$this->getRequestParameter('src_directory');
        if(is_dir($this->srcPath . '/fonts')){
            $this->srcPaths = array(
                'font'  =>  $this->srcPath . '/fonts',
                'tao'   =>  $this->srcPath . '/fonts/tao',
                'style' =>  $this->srcPath
            );

            $this->srcFonts = scandir($this->srcPaths['font']);

            // load font configuration along with defaults
            $data  = json_decode(file_get_contents($this->srcPaths['style'] . '/selection.json', 'r'));
            $prefs  = json_decode(file_get_contents($this->taoMaticPath . '/selection.prefs.json', 'r'));


            $dataName = array();

            // build code for PHP icon class and tao-*.scss files
            foreach($data->icons as $iconProperties){
                $dataName[] = $iconProperties->properties->name;
            }

            if($errors = $this->isSelectionCorrect($dataName) != true){
                throw new \Exception('Your selection.json file contains error, missing : ' . implode(", ",$errors));
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

            // read original stylesheet
            if(!$cssContent = file_get_contents($this->srcPaths['style'] . '/style.css')){
                throw new \Exception('Unable to read the file : ' . $this->srcPaths['style'] . '/style.css');
            }

            // font-face
            $cssContentArr = explode('[class^="icon-"]',$cssContent);
            $this->iconCss['def'] = str_replace('fonts/tao.', 'fonts/tao/tao.',$cssContentArr[0]).'\n';
            // font-family etc.
            $cssContentArr = explode('.icon',$cssContentArr[1]);
            $this->iconCss['vars'] = str_replace(', [class*=" icon-"]','%tao-icon-setup',$cssContentArr[0]);

            // the actual css code
            $this->iconCss['classes'] = '@import \'inc/tao-icon-vars.scss\';\n[class^="icon-"], [class*=" icon-"] { @extend %tao-icon-setup; }\n';

            // icomoon config file, collect icons and re-configure
            //$handler = fopen($this->srcPaths['style'] . '/selection.json', 'w');

            //if(!$handler){
            //    throw new \Exception('Unable to open file : ' . $this->srcPaths['style'] . '/selection.json');
            //}

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
            $handler = fopen($this->distroPaths['style'] . '/_tao-icon-' . $key . '.scss', 'w');

            if(!$handler){
                throw new \Exception('Unable to open file : ' . $this->srcPaths['style'] . '/selection.json');
            }
            foreach ($this->iconCss as $key => $value){
                fwrite($handler, $this->doNotEdit.$this->iconCss[$key]);

            }
            fclose($handler);

            // write PHP icon class
            $phpContent = file_get_contents($this->taoMaticPath . '/class.Icon.tpl');

            $handler = fopen($this->distroPaths['helpers'] . '/class.Icon.php', 'w');

            if(!$handler){
                throw new \Exception('Unable to open file : ' . $this->srcPaths['style'] . '/selection.json');
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
                throw new \Exception('Unable to open file : ' . $this->srcPaths['style'] . '/selection.json');
            }
            fwrite($handler,$cssContent);
            fclose($handler);

            $listing = $this->ListIn($this->distroPath);
            $readmeContent = file_get_contents($this->taoMaticPath . '/readme.md');
            $readmeContent = str_replace('{LISTING}', $listing, $readmeContent);


            $this->setData('message',$readmeContent);
        }
        else{
            $this->setData('message', $this->srcPath .__(' is not a valid directory'));
        }

        $this->setView('fontsConversion/view.tpl');

    }

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
