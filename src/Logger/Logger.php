<?php

namespace Crunz\Logger;

use Crunz\Configuration\Configurable;
use Crunz\Singleton;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\MongoDBFormatter;
use Monolog\Handler\MongoDBHandler;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger as MonologLogger;

class Logger extends Singleton {
    use Configurable;
    protected $logger;
    protected $levels = [
        'debug'     => MonologLogger::DEBUG,
        'info'      => MonologLogger::INFO,
        'notice'    => MonologLogger::NOTICE,
        'warning'   => MonologLogger::WARNING,
        'error'     => MonologLogger::ERROR,
        'critical'  => MonologLogger::CRITICAL,
        'alert'     => MonologLogger::ALERT,
        'emergency' => MonologLogger::EMERGENCY
    ];

    public function __construct( \Monolog\Logger $logger ) {
        $this->configurable();
        $this->logger = $logger;
    }

    public function addStream( $stream, $path = '/dev/null', $level, $bubble = true ) {
        switch ( $stream ) {
            case 'mongodb':
                $handler = new MongoDBHandler(
                    new \MongoClient( 'mongodb://' . $this->config( 'mongodb.host' ) . ':' . $this->config( 'mongodb.port' ) ),
                    $this->config( 'mongodb.dbname' ),
                    $path,
                    $this->parseLevel( $level ),
                    $bubble );
                $formatter = new MongoDBFormatter( 5, true );
                break;

            case 'file':
                $handler   = new StreamHandler( $path, $this->parseLevel( $level ), $bubble );
                $formatter = new LineFormatter( "[%datetime%] %message%\n", null, false, false );
                break;

            default:
                $handler   = new NullHandler( $path, $this->parseLevel( $level ), $bubble );
                $formatter = new LineFormatter( "[%datetime%] %message%\n", null, false, false );
                break;
        }
        $this->logger->pushHandler( $handler );
        $handler->setFormatter( $formatter );

        return $this;
    }

    public function info( $content, $context = [] ) {
        return $this->write( $content, 'info', $context );
    }

    public function error( $message, $context = [] ) {
        return $this->write( $message, 'error', $context );
    }

    public function write( $content, $level, $context = [] ) {
        return $this->logger->{$level}( $content, $context );
    }

    protected function parseLevel( $level ) {
        if ( isset( $this->levels[$level] ) ) {
            return $this->levels[$level];
        }
        throw new InvalidArgumentException( 'Invalid log level.' );
    }
}