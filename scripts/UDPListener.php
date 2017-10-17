<?php

class UDPListener
{

    private static $COLOR = array(
        '0' => '1;30', // dark grey
        '1' => '0;37', // light grey
        '2' => '0;32', // green
        '3' => '1;33', // yellow
        '4' => '1;31', // red
        '5' => '1;31', // red

    );

    private static $FILTER = array(
        "/pg_fetch_row\(\): Unable to jump to row -2 on PostgreSQL result index/"
    );

    private $url;
    private $socket;
    private $filter;
    private $showTime;

    public function __construct($pUrl, $pOptions = [])
    {
        $this->url = $pUrl;
        $this->showTime = isset($pOptions['time']) ? $pOptions['time'] : false;
        $this->filter = isset($pOptions['filter']) ? $pOptions['filter'] : null;
        if ($this->filter && substr($this->filter, 0, 1) != '/' && substr($this->filter, -1) != '/') {
            $this->filter = "/$this->filter/";
        }
        $this->socket = stream_socket_server($this->url, $errno, $errstr, STREAM_SERVER_BIND);
        if (!$this->socket) {
            die("$errstr ($errno)");
        }
    }

    public function listen()
    {
        echo "Listening to $this->url\n";
        do {
            $received = stream_socket_recvfrom($this->socket, 33000, 0, $peer);

            $code = json_decode($received, true); // drop "[".$code['s']."] ".

            $blacklisted = false;
            foreach (self::$FILTER as $pattern) {
                if (preg_match($pattern, $code['d']) > 0)
                    $blacklisted = true;
            }

            if (!$blacklisted) {
                $this->render($code);
            }

        } while ($received !== false);
    }

    public function render($pData)
    {

        $prefix = !empty($pData['p']) ? '[' . $pData['p'] . ']' : '';
        $message = "\033[" . self::$COLOR[$pData['s']] . 'm' . $prefix . $pData['d'] . " (" . implode(',', $pData['t']) . ")\033[0m\n";
        if (isset($pData['b']) && ($pData['s'] >= 3)) {
            $message .= $this->renderBacktrace($pData['b']);
        } elseif (in_array('DEPRECATED', $pData['t']) && isset($pData['b'][1])) {
            $message .= "\t" . $pData['b'][1]['file'] . '(' . $pData['b'][1]['line'] . ")\n";
        }

        if (!$this->filter || preg_match($this->filter, $message)) {
            if ($this->showTime) {
                $now = DateTime::createFromFormat('U.u', microtime(true));
                echo $now->format('[H:i:s.u]');
            }
            echo $message;
        }
    }

    public function renderBacktrace($pData)
    {
        $file = array();
        $maxlen = 0;
        foreach ($pData as $row)
            if (isset($row["file"])) {
                $file[] = $row["file"];
                if (strlen($row["file"]) > $maxlen)
                    $maxlen = strlen($row["file"]);
            }
        $prefixlen = strlen($this->getCommonPrefix($file));

        $backtrace = '';
        foreach ($pData as $row) {
            $string = ''
                . (isset($row["file"]) ? substr($row["file"], $prefixlen) : '---')
                . (isset($row["line"]) ? "(" . $row["line"] . ")" : '');
            $string = str_pad($string, $maxlen - $prefixlen + 3 + 5);
            $string .= ''
                . (isset($row["class"]) ? $row["class"] . '::' : '')
                . (isset($row["function"]) ? $row["function"] . "()" : '---');
            $backtrace .= "\t" . $string . "\n";
        }
        return $backtrace;
    }

    public function getCommonPrefix($pData)
    {
        $prefix = array_shift($pData);  // take the first item as initial prefix
        $length = strlen($prefix);
        // compare the current prefix with the prefix of the same length of the other items
        foreach ($pData as $item) {
            // check if there is a match; if not, decrease the prefix by one character at a time
            while ($length && substr($item, 0, $length) !== $prefix) {
                $length--;
                $prefix = substr($prefix, 0, -1);
            }
            if (!$length) {
                break;
            }
        }

        return $prefix;
    }
}

class Parameters
{
    private $parameters;

    public function __construct(array $options, array $longOptions = [])
    {
        $this->parameters = getopt(implode('', $options), $longOptions);
        $this->checkMandatoryValues($options, $longOptions);
    }

    private function checkMandatoryValues($options, $longOptions)
    {
        global $argv;

        $mandatory = array_merge(
            $this->getMandatoryValues($options, '-'),
            $this->getMandatoryValues($longOptions, '--')
        );

        foreach ($argv as $arg) {
            if (isset($mandatory[$arg]) && !isset($this->parameters[$mandatory[$arg]])) {
                die("Missing param value for $arg\n");
            }
        }
    }

    private function getMandatoryValues($options, $prefix)
    {
        $mandatory = [];
        foreach ($options as $opt) {
            if (substr($opt, -1) == ':' && substr($opt, -2) != '::') {
                $name = substr($opt, 0, -1);
                $mandatory[$prefix . $name] = $name;
            }
        }
        return $mandatory;
    }

    public function has($short, $long = null)
    {
        return isset($this->parameters[$short]) || ($long && isset($this->parameters[$long]));
    }

    public function get($short, $long = null, $default = null)
    {
        if ($long && isset($this->parameters[$long])) {
            return $this->parameters[$long];
        }
        if (isset($this->parameters[$short])) {
            return $this->parameters[$short];
        }
        return $default;
    }
}

$parameters = new Parameters(['h:', 'p:', 'f:', 't'], ['host:', 'port:', 'filter:', 'time']);

$url = 'udp://' . $parameters->get('h', 'host', '127.0.0.1') . ':' . $parameters->get('p', 'port', '5775');

$udr = new UDPListener($url, [
    'filter' => $parameters->get('f', 'filter'),
    'time' => $parameters->has('t', 'time')
]);
$udr->listen();
