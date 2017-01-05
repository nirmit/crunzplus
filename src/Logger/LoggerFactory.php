<?php

namespace Crunz\Logger;

use Crunz\Logger\Logger;
use Monolog\Logger as MonologLogger;

class LoggerFactory {
    public static function makeOne( Array $streams = [] ) {
        $logger = new Logger( new MonologLogger( 'crunz' ) );
        foreach ( $streams as $level => $data ) {
            if ( !$data ) {
                continue;
            }

            $logger->addStream( $data[0], $data[1], $level, false );
        }

        return $logger;
    }

}