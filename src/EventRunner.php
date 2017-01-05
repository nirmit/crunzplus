<?php

namespace Crunz;

use Crunz\Configuration\Configurable;
use Crunz\Logger\LoggerFactory;

class EventRunner {
    use Configurable;
    protected $schedules = [];
    protected $invoker;
    protected $logger;
    protected $mailer;

    public function __construct() {
        $this->configurable();
        $this->logger = LoggerFactory::makeOne( [
            'info'  => [$this->config( 'output.stream' ), $this->config( 'output.endpoint' )],
            'error' => [$this->config( 'errors.stream' ), $this->config( 'errors.endpoint' )]
        ] );
        $this->invoker = new Invoker();
        $this->mailer  = new Mailer();
    }

    public function handle( Array $schedules = [] ) {
        $this->schedules = $schedules;
        foreach ( $this->schedules as $schedule ) {
            $this->invoke( $schedule->beforeCallbacks());
            $events = $schedule->events();
            foreach ( $events as $event ) {
                $this->start( $event );
            }
        }
        $this->ManageStartedEvents();
    }

    protected function start( Event $event ) {
        if ( !$event->nullOutput()) {
            $output        = strlen( $event->output ) < 2 ? $this->config( 'output.endpoint' ) : $event->output;
            $event->logger = LoggerFactory::makeOne( ['info' => [$this->config( 'output.stream' ), $output]] );
        }
        $event->outputStream = ( $this->invoke( $event->beforeCallbacks()));
        $event->start();
    }

    protected function ManageStartedEvents() {
        while ( $this->schedules ) {
            foreach ( $this->schedules as $scheduleKey => $schedule ) {
                $events = $schedule->events();
                foreach ( $events as $eventKey => $event ) {
                    $proc = $event->getProcess();
                    if ( $proc->isRunning()) {
                        continue;
                    }

                    if ( $proc->isSuccessful()) {
                        $event->outputStream .= $proc->getOutput();
                        $event->outputStream .= $this->invoke( $event->afterCallbacks());
                        $this->handleOutput( $event );
                    } else {
                        $this->invoke( $schedule->errorCallbacks(), [$event] );
                        $this->handleError( $event );
                    }
                    $schedule->dismissEvent( $eventKey );
                }
                if ( !count( $schedule->events())) {
                    $this->invoke( $schedule->afterCallbacks());
                    unset( $this->schedules[$scheduleKey] );
                }
            }
        }
    }

    protected function invoke( array $callbacks = [], Array $parameters = [] ) {
        $output = '';
        foreach ( $callbacks as $callback ) {
            $output .= $this->invoker->call( $callback, $parameters, true );
        }

        return $output;
    }

    protected function handleOutput( Event $event ) {
        $logged = false;
        if ( $this->config( 'output.log' ) ) {
            $this->logger->info( $this->formatEventOutput( $event ), ['run_id' => $event->getId(), 'run_dt' => date( 'Y-m-d H:i:s' )] );
            $logged = true;
        }

        if ( !$event->nullOutput() && $event->output != $this->config( 'output.log' ) && $event->output != '' ) {
            $event->logger->info( $this->formatEventOutput( $event ), ['run_id' => $event->getId(), 'run_dt' => date( 'Y-m-d H:i:s' )] );
            $logged = true;
        }

        if ( !$logged ) {
            $this->display( $event->getOutputStream() );
        }

        if ( $this->config( 'output.email' )) {
            $this->mailer->send(
                'Event Output: ' . (( $event->description ) ? $event->description : $event->getId()),
                $this->formatEventOutput( $event )
            );
        }
    }

    protected function handleError( Event $event ) {
        if ( $this->config( 'errors.log' )) {
            $this->logger->error( $this->formatEventError( $event ));
        } else {
            $this->display( $event->getProcess()->getErrorOutput());
        }

        if ( $this->config( 'errors.email' )) {
            $this->mailer->send(
                'Event Error Report:' . (( $event->description ) ? $event->description : $event->getId()),
                $this->formatEventError( $event )
            );
        }
    }

    protected function formatEventOutput( Event $event ) {
        return $event->description . '(' . $event->getCommandForDisplay() . ') ' . PHP_EOL . $event->outputStream . PHP_EOL;
    }

    protected function formatEventError( Event $event ) {
        return $event->description . '(' . $event->getCommandForDisplay() . ') ' . PHP_EOL . $event->getProcess()->getErrorOutput()
            . PHP_EOL;
    }

    protected function display( $output ) {
        print is_string( $output ) ? $output : '';
    }
}