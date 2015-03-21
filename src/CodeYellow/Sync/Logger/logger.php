<?php
namespace CodeYellow\Sync\Logger;

use Psr\Log\LogLevel;

trait Logger
{
    private $loggerInstance;

    /**
     * Sets a logger instance on the object
     *
     * @param LoggerInterface $logger
     * @return null
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->loggerInstance = $logger;
    }

    /**
     * Get the used logger instance
     * @return LoggerInterface $logger
     */
    public function getLogger()
    {
        return $this->loggerInstance;
    }
    
    /**
     * Checks if a logger is available else
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public function log($level, $message, $context = array())
    {
        // If a logger instance is set, log the message
        if (!is_null($this->loggerInstance)) {
            // prefix with an indicator that it are our logss
            $message  = 'CodeYellow\Sync: ' . $message;
            $this->loggerInstance->log($level, $message, $context);
        }
    }
}
