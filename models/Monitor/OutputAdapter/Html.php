<?php
/**
 *
 */

namespace oat\taoDevTools\models\Monitor\OutputAdapter;

use oat\taoDevTools\models\Monitor\Chunk\RequestChunk;

class Html extends AbstractAdapter
{

    protected $filePath;

    protected $request;

    /**
     * Delegate constructor
     */
    public function init() {


        if(is_null($this->filePath)) {
            $this->filePath = FILES_PATH . 'taoDevTools/monitor/html/';
        }

        $this->validateFilePath();


    }

    /**
     * Ensure that the path exists and is writable
     */
    public function validateFilePath() {

        if(!file_exists($this->filePath)) {
            if(!mkdir($this->filePath, 0777, true)) {
                throw new \Exception('Could not create path ' . $this->filePath . ', please check permissions');
            }
        }

        if(! is_writable($this->filePath) ) {
            throw new \Exception('Could not write in ' . $this->filePath . ', please check folder permissions');
        }
    }

    /**
     * Called by the monitor at construct time
     *
     * @param RequestChunk $request
     *
     * @return mixed
     */
    public function startMonitoring(RequestChunk $request) {
        // TODO: Implement startMonitoring() method.
    }

    /**
     * Called by the monitor at destruct time
     *
     * @param RequestChunk $request
     *
     * @return mixed
     */
    public function endMonitoring(RequestChunk $request) {

        $this->request = $request;

        $totalDuplicatedCalls = count($request->getDuplicatedCalls());

        if($this->writeOnlyDuplicated && (!$totalDuplicatedCalls)) {
            return;
        }

        $totalCalls = count($request->getCalls());

        $html = $this->render($request);

        $filename = $totalCalls . '.' . $totalDuplicatedCalls . '.' . $request->getUrlSlug() . '.mnt.html';

        $this->writeFile($filename, $html);

        //Create the index
        $fileList = glob($this->filePath . '*.mnt.html');

        $offset = strlen($this->filePath);

        $fileList = array_map(function($e) use ($offset){ return substr($e,$offset);}, $fileList);

        $index = $this->partial('index', ['files' => $fileList]);

        $this->writeFile('index.html', $index);

    }

    /**
     * Render the layout
     * @return string
     */
    public function render(RequestChunk $request) {

        ob_start();

        include __DIR__ . '/Html/view/layout.phtml';

        return ob_get_clean();
    }

    /**
     * Render a partial script
     *
     * @param string $script Name of the script
     * @param array  $data Will be extracted in the script variable scope
     *
     * @return string
     */
    public function partial($script, $data = []) {

        extract($data);

        ob_start();

        include __DIR__ . '/Html/view/' . $script . '.phtml';

        return ob_get_clean();
    }

    /**
     * Write to a file
     * @param $fileName
     * @param $data
     */
    public function writeFile($fileName, $data) {

        if(strlen($fileName) > 250) {
            $path_parts = pathinfo($fileName);
            $fileNamePart = $path_parts['filename'];

            $fileName = sprintf('%s%05d', substr($fileNamePart, 0, 244 ), strlen($fileName)) . '.html';
        }

        file_put_contents($this->filePath . $fileName , $data);
    }

    /**
     * @return RequestChunk
     */
    public function getRequest() {
        return $this->request;
    }

    /**
     * @param mixed $filePath
     *
     * @return Html
     */
    public function setFilePath($filePath) {
        $this->filePath = $filePath;

        return $this;
    }


}