<?php
class icon {

  private static $instance;

  private $iconClassFile;

  private $iconClassFileExists = false;

  private function __construct() {
    $this -> iconClassFile = dirname(__DIR__) . '/tao-distro/helpers/class.Icon.php';
    $this -> iconClassFileExists = is_readable($this -> iconClassFile); 
  }


  private static function getInstance() {
    if(!isset(self::$instance)) {
      $class = __CLASS__;
      self::$instance = new $class;
    }
    return self::$instance;
  }

  public static function classExists() {
    $selfObj = self::getInstance();
    return $selfObj -> iconClassFileExists;
  }

  public static function getIconClass() {
    $selfObj = self::getInstance();
    return $selfObj -> iconClassFile;
  }


  /**
   * Test the icon class for errors
   *   
   * @throw Exception
   * @return bool
   */
  public static function test() {
    $selfObj = self::getInstance();
    
    if(!$selfObj -> iconClassFileExists) {
      throw new Exception('File not found: ' . $selfObj -> iconClassFile, 1); 
    }
    
    ob_start();    
    $syntaxCheck = system('php -l ' . $selfObj -> iconClassFile);
    $parseResult = ob_get_clean();

    if(false === strpos($parseResult, 'No syntax errors detected')) {
      $parseResult = strtok($parseResult, PHP_EOL);
      throw new Exception($parseResult, 1); 
    }

    require_once $selfObj -> iconClassFile;
    if(!class_exists('tao_helpers_Icon')) {
      throw new Exception('Class tao_helpers_Icon does not exist', 1); 
    }

    $reflection = new ReflectionClass('tao_helpers_Icon');

    foreach($reflection -> getConstants() as $icon) {
      if(!preg_match('~^icon-[\w-]+$~', $icon)) {
        throw new Exception('Error in icon syntax: ' . $icon, 1); 
      }
    }   
  }
}

try {  
  icon::test();
}
catch (Exception $e) {
  print $e -> getMessage();
  file_put_contents(__DIR__ . '/errors/' . date('Y-m-d') . '-error.log', date('Y-m-d H:i:s') . ' - ' . $e -> getMessage() . PHP_EOL, FILE_APPEND);
  if(icon::classExists()) {
    rename(icon::getIconClass(), __DIR__ . '/errors/' . date('Y-m-d') . '-broken.class.Icon.php');
  }
}