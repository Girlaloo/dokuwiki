<?php

namespace dokuwiki;

class Logger
{
    const LOG_ERROR = 'error';
    const LOG_DEPRECATED = 'deprecated';
    const LOG_DEBUG = 'debug';

    /** @var Logger[] */
    static protected $instances;

    /** @var string what kind of log is this */
    protected $facility;

    /**
     * Logger constructor.
     *
     * @param string $facility The type of log
     */
    protected function __construct($facility)
    {
        $this->facility = $facility;
    }

    /**
     * Return a Logger instance for the given facility
     *
     * @param string $facility The type of log
     * @return Logger
     */
    static public function getInstance($facility = self::LOG_ERROR)
    {
        if (self::$instances[$facility] === null) {
            self::$instances[$facility] = new Logger($facility);
        }
        return self::$instances[$facility];
    }

    /**
     * Log a message to the facility log
     *
     * @param string $message The log message
     * @param mixed $details Any details that should be added to the log entry
     * @param string $file A source filename if this is related to a source position
     * @param int $line A line number for the above file
     * @return bool
     */
    public function log($message, $details = null, $file = '', $line = 0)
    {
        // details are logged indented
        if ($details && !is_string($details)) {
            $details = json_encode($details, JSON_PRETTY_PRINT);
            $details = explode("\n", $details);
            $loglines = array_map(function ($line) {
                return '  ' . $line;
            }, $details);
        } elseif ($details) {
            $loglines = [$details];
        } else {
            $loglines = [];
        }

        $logline = gmdate('c') . "\t" . $message;
        if ($file) {
            $logline .= "\t$file";
            if ($line) $logline .= "($line)";
        }

        array_unshift($loglines, $logline);
        return $this->writeLogLines($loglines);
    }

    /**
     * Write the given lines to today's facility log
     *
     * @param string[] $lines the raw lines to append to the log
     * @return bool true if the log was written
     */
    protected function writeLogLines($lines)
    {
        global $conf;
        $logfile = $conf['logdir'] . '/' . $this->facility . '/' . gmdate('Y-m-d') . '.log';
        return io_saveFile($logfile, join("\n", $lines) . "\n", true);
    }
}