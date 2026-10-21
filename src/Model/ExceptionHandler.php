<?php
namespace Foolz\FoolFrame\Model;

use Psr\Log\LoggerInterface;

class ExceptionHandler extends \Symfony\Component\Debug\ExceptionHandler
{
    /**
     * @var LoggerInterface
     */
    protected $logger = null;

    /**
     * @var LoggerInterface
     */
    protected $logger_trace = null;

    /**
     * Classic logger
     *
     * @param LoggerInterface $logger
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Logger that will print also stack trace
     *
     * @param LoggerInterface $logger
     */
    public function setLoggerTrace(LoggerInterface $logger)
    {
        $this->logger_trace = $logger;
    }

    /**
     * Sends a response for the given Throwable (Exception or Error).
     *
     * Updated for PHP 7+ / PHP 8+ compatibility. Old code only accepted \Exception,
     * which caused a fatal error when a real \Error (e.g. TypeError) was thrown.
     *
     * @param \Throwable $exception A \Throwable instance (Exception or Error)
     *
     * @see sendPhpResponse
     * @see createResponse
     */
    public function handle(\Throwable $exception)
    {
        if ($this->logger !== null) {
            $this->logger->error($exception->getMessage());
        }

        if ($this->logger_trace !== null) {
            $string = $exception->getMessage()."\r\n";
            foreach ($exception->getTrace() as $trace) {
                $string .= '    ';
                if (isset($trace['file'])) {
                    $string .= 'at '.$trace['file'].'('.$trace['line'].') ';
                }
                if (isset($trace['class'])) {
                    $string .= 'in '. $trace['class'].$trace['type'];
                }
                if (isset($trace['function'])) {
                    $args = $trace['args'] ?? [];
                    $string .= $trace['function'].'('.$this->stringify($args).')';
                }
                $string .= "\r\n";
            }
            $this->logger_trace->error($string);
        }

        if ($exception instanceof \Exception) {
            // Old Symfony 2.8 ExceptionHandler only understands \Exception, not \Error
            if (class_exists('Symfony\Component\HttpFoundation\Response')) {
                $this->createResponse($exception)->send();
            } else {
                $this->sendPhpResponse($exception);
            }
        } else {
            // We got a real Error (TypeError, etc.). The old Symfony code can't handle it.
            // Send a minimal error page so the user at least sees something useful.
            if (!headers_sent()) {
                header('HTTP/1.1 500 Internal Server Error');
            }
            echo '<h1>Application Error</h1>';
            echo '<p><strong>' . htmlspecialchars($exception->getMessage()) . '</strong></p>';
            echo '<pre>' . htmlspecialchars($exception->getTraceAsString()) . '</pre>';
            exit(1);
        }
    }

    /**
     * Creates an acceptable representation of $trace['args']
     *
     * @param     $array
     * @param int $depth
     *
     * @return string
     */
    public function stringify($array, $depth = 0)
    {
        if ($depth > 2) {
            return '[...]';
        }

        if (!is_array($array)) {
            return '[]';
        }

        $result = [];
        foreach ($array as $a) {
            if (is_array($a)) {
                $result[] = '['.$this->stringify($a, $depth + 1).']';
                continue;
            }

            if (is_object($a)) {
                $result[] = get_class($a);
                continue;
            }

            $result[] = $a;
        }

        return implode(', ', $result);
    }
}
