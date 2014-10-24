<?php

class icon
{

    private $iconClassFile;

    private $iconClassFileExists = false;

    public function __construct($path)
    {
        $this->iconClassFile       = $path . '/helpers/class.Icon.php';
        $this->iconClassFileExists = is_readable($this->iconClassFile);
    }

    public function classExists()
    {
        return $this->iconClassFileExists;
    }

    public function getIconClass()
    {
        return $this->iconClassFile;
    }


    /**
     * Test the icon class for errors
     *
     * @throws Exception
     */
    public function test()
    {

        if (!$this->iconClassFileExists) {
            throw new Exception('File not found: ' . $this->iconClassFile, 1);
        }

        ob_start();
        system('php -l ' . $this->iconClassFile);
        $parseResult = ob_get_clean();

        if (false === strpos($parseResult, 'No syntax errors detected')) {
            $parseResult = strtok($parseResult, PHP_EOL);
            throw new Exception($parseResult, 1);
        }
    }
}