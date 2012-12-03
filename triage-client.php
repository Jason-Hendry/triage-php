<?php
$triageClient = null;
function triageError($url, $project, $errno, $errstr, $errfile, $errline, $errcontext)
{
    global $triageClient;
    if ($triageClient === null) {
        $triageClient = new TriageZeroMQMessagePackClient(['uri' => $url]);
    }
    $errorMap = [
        1 => 'Error',
        2 => 'Warning',
        8 => 'Notice',
        16 => 'Core Error',
        32 => 'Core Warning',
        64 => 'Compile Error',
        128 => 'Compile Warning',
        256 => 'User Error',
        512 => 'User Warning',
        1024 => 'User Notice',
        2048 => 'Strict',
        4096 => 'Recoverable Error',
        8192 => 'Deprecated',
        16384 => 'User Deprecated',
    ];
    $errorType = isset($errorMap[$errno]) ? $errorMap[$errno] : 'Unknown ' . $errno;
    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    $githash = is_file(dirname(__FILE__) . '/../.git/refs/heads/master')
        ? trim(file_get_contents(dirname(__FILE__) . '/../.git/refs/heads/master'))
        : (is_file(dirname(__FILE__) . '/commit.txt')
            ? trim(file_get_contents(dirname(__FILE__) . '/commit.txt'))
            : 'Unknown');
    $triageClient->logError([
        'message' => $errstr,
        'project' => $project,
        'type' => $errorType,
        'language' => 'php',
        'timestamp' => time(),
        'line' => $errline,
        'file' => $errfile,
        'context' => $errcontext,
        'backtrace' => $backtrace,
        'commithash' => $githash,
    ]);
}

/**
 * ZeroMQMessagePack Wrapper
 */
class TriageZeroMQMessagePackClient
{
    private $_socket;

    public static function construct($args)
    {
        return new self($args['uri']);
    }

    public function __construct($uri)
    {
        /* Create new queue object */
        $this->_socket = new ZMQSocket(
            new ZMQContext(),
            ZMQ::SOCKET_PUB,
            'socket1'
        );

        $this->_socket->connect($uri);
    }

    public function logError($error)
    {
        $this->_send($error);
        return $this;
    }

    public function logMessage($level, $message)
    {
        $this->_send(array(
            'message' => $message,
            'level' => $level,
            'time' => time()
        ));

        return $this;
    }

    private function _send($error)
    {
        $this->_socket->send($this->_pack($error), ZMQ::MODE_NOBLOCK);
    }

    private function _pack($data)
    {
        $msgPack = new MessagePack();
        return $msgPack->pack($data);
    }
}
