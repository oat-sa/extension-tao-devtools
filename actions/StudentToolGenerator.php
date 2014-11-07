<?php
/**
 * Creates a new student tool based on a template
 *
 * Date: 06/11/14
 * Time: 13:45
 */

namespace oat\taoDevTools\actions;

use Jig\Utils\StringUtils;


class StudentToolGenerator extends \tao_actions_CommonModule {

    /**
     * @var array
     */
    private $requiredArgs = array(
        'client'       => 'Client Name (PARCC or OAT)',
        'title'        => 'Tool Name',
        'transparent'  => '(1 or 0)',
        'rotatable'    => '(1 or 0)',
        'movable'      => '(1 or 0)',
        'adjustx'      => '(1 or 0)',
        'adjusty'      => '(1 or 0)',
    );

    /**
     * @var array
     */
    private $data = array();


    public function index()
    {
        $this->setView('studentToolGenerator/view.tpl');
    }

    /**
     * Start generator
     */
    public function run() {
        try {
            $this -> generateTool();
        }
        catch(\Exception $e) {
            return $this -> returnJson(array('error' => $e -> getMessage()));
        }
    }

    /**
     * Take template and create the tool
     *
     * @throws \Exception
     */
    protected function generateTool() {

        $this->data = $this->getMappedArguments();
        $generatorPath = str_replace(DIRECTORY_SEPARATOR, '/', dirname(__DIR__)) . '/studentToolGenerator';
        $targetPath    = $generatorPath . '/generated-code/' . $this->data['client'] . '/' . $this->data['tool-id'];
        $templatePath  = $generatorPath . '/template';
        if(is_dir($targetPath)) {
            throw new \Exception (sprintf('Tool %s already exists', $this->data['tool-id']));
        }

        $objects = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($templatePath), \RecursiveIteratorIterator::SELF_FIRST);

        $patterns = $this -> getPatterns();
        $replacements = $this -> getReplacements();

        foreach($objects as $tplFile => $cursor){
            if(in_array(basename($tplFile), array('.', '..'))) {
                continue;
            }

            $toolFile = str_replace($templatePath, $targetPath, $tplFile);

            if($cursor->isDir() && !is_dir($toolFile)) {
                mkdir($toolFile, 0755, true);
            }
            else if($cursor->isFile()){
                $toolFile = dirname($toolFile) . '/' . str_replace('template', $this->data['tool-base'], basename($toolFile));
                $toolContent = str_replace($patterns, $replacements, file_get_contents($tplFile));
                file_put_contents($toolFile, $toolContent);
            }
        }
    }


    /**
     * Generate all required data from _POST
     *
     * @return array
     * @throws \Exception
     */
    protected function getMappedArguments()
    {
        $argHelp = "Required arguments are:\n";
        foreach ($this->requiredArgs as $key => $value) {
            $argHelp .= $key . ': ' . $value . "\n";
        }

        foreach ($this->requiredArgs as $key => $value) {
            if (!isset($_POST[$key])) {
                throw new \Exception($argHelp);
                break;
            }
        }
        $data = $_POST;

        // trim all, cast 0|1 too bool
        foreach ($data as &$value) {
            $value = trim($value);
            if (in_array($value, array('0', '1'))) {
                $value = (bool)$value;
            }
        }
        $data['client']           = strtoupper($data['client']);
        $data['tool-title']       = StringUtils::removeSpecChars($data['title']);
        $data['tool-base']        = StringUtils::removeSpecChars($data['tool-title']);
        $data['tool-fn']          = StringUtils::camelize($data['tool-base']);
        $data['tool-obj']         = ucfirst($data['tool-fn']);
        $data['tool-id']          = strtolower($data['client']) . $data['tool-obj'];
        $data['tool-date']        = date('Y-m-d H:i:s');
        $data['is-transparent']   = $this->boolToString($data['transparent']);
        $data['is-rotatable-tl']  = $this->boolToString($data['rotatable']); // default position of rotator
        $data['is-rotatable-tr']  = $this->boolToString(!$data['adjustx'] && !$data['adjusty']); // only visible when not adjustable
        $data['is-rotatable-br']  = $this->boolToString(!$data['adjustx'] && !$data['adjusty']); // only visible when not adjustable
        $data['is-rotatable-bl']  = $this->boolToString($data['rotatable']); // also default position of rotator
        $data['is-movable']       = $this->boolToString($data['movable']);
        $data['is-adjustable-x']  = $this->boolToString($data['adjustx']);
        $data['is-adjustable-y']  = $this->boolToString($data['adjusty']);
        $data['is-adjustable-xy'] = $this->boolToString($data['adjustx'] && $data['adjusty']);

        unset($data['title']);
        unset($data['transparent']);
        unset($data['rotatable']);
        unset($data['movable']);
        unset($data['adjustx']);
        unset($data['adjusty']);


        return $data;
    }

    /**
     * First arg for str_replace
     *
     * @return array
     */
    protected function getPatterns() {
        $patterns = array();
        foreach($this->data as $pattern => $replacement) {
            $patterns[]     = '{' . $pattern . '}';
        }
        return $patterns;
    }

    /**
     * Second arg for str_replace
     *
     * @return array
     */
    protected function getReplacements() {
        return array_values($this -> data);
    }

    /**
     * This looks a bit dodgy at first glance but it's not. These 'Bool strings' are used to replace placeholders
     * in the generated JavaScript.
     *
     * @param $arg
     * @return string
     */
    protected function boolToString($arg)
    {
        return false !== $arg ? "true" : "false";
    }
} 