<?php
class icon {

  private static $instance;

  private $iconClassFile;

  private $iconClassFileExists = false;

  public function __construct($path) {
    $this -> iconClassFile = $path. '/helpers/class.Icon.php';
    $this -> iconClassFileExists = is_readable($this -> iconClassFile); 
  }

  public function classExists() {
    return $this->iconClassFileExists;
  }

  public function getIconClass() {
    return $this->iconClassFile;
  }


  /**
   * Test the icon class for errors
   *   
   * @throw Exception
   * @return bool
   */
  public function test() {

    if(!$this -> iconClassFileExists) {
      throw new Exception('File not found: ' . $this -> iconClassFile, 1);
    }
    
    ob_start();    
    $syntaxCheck = system('php -l ' . $this -> iconClassFile);
    $parseResult = ob_get_clean();

    if(false === strpos($parseResult, 'No syntax errors detected')) {
      $parseResult = strtok($parseResult, PHP_EOL);
      throw new Exception($parseResult, 1); 
    }

    require_once $this -> iconClassFile;
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