<?php

namespace Crunz;

use Crunz\Configuration\Configurable;
use Crunz\Logger\LoggerFactory;

class ErrorHandler extends Singleton {
    use Configurable;
    protected $logger;
    protected $mailer;

    public function __construct() {
        $this->configurable();
        $this->logger = LoggerFactory::makeOne( ['error' => [$this->config( 'errors.stream' ), $this->config( 'errors.endpoint' )]] );
        $this->mailer = new Mailer();
    }

    public function set() {
        ob_start( [ & $this, 'catchErrors'] );
    }

    public function catchErrors( $buffer ) {
        if ( !is_null( error_get_last() ) ) {
            $this->logger->error( $buffer );
            if ( $this->config( 'errors.email' ) ) {
                $this->mailer->send( 'Fatal Error Report', $buffer );
            }
        }

        return $buffer;
    }
}