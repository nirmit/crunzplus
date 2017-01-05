<?php

namespace Crunz;

use Crunz\Configuration\Configurable;
use Symfony\Component\Process\ProcessUtils;

class Schedule {
    use Configurable;
    protected $events          = [];
    protected $beforeCallbacks = [];
    protected $afterCallbacks  = [];
    protected $errorCallbacks  = [];

    public function __construct() {
        $this->configurable();
    }

    public function run( $command, array $parameters = [] ) {
        if ( is_string( $command ) && count( $parameters ) ) {
            $command .= ' ' . $this->compileParameters( $parameters );
        }
        $this->events[] = $event = new Event( $this->id(), $command );

        return $event;
    }

    protected function id() {
        while ( true ) {
            $id = uniqid();
            if ( !array_key_exists( $id, $this->events ) ) {
                return $id;
            }
        }
    }

    protected function compileParameters( array $parameters ) {
        return implode( ' ', array_map( function ( $value, $key ) {
            return is_numeric( $key ) ? $value : $key . '=' . ( is_numeric( $value ) ? $value : ProcessUtils::escapeArgument( $value ) );
        }, $parameters, array_keys( $parameters ) ) );
    }

    public function pingBefore( $url ) {
        return $this->before( function () use ( $url ) {
            ( new HttpClient )->get( $url );
        } );
    }

    public function thenPing( $url ) {
        return $this->then( function () use ( $url ) {
            ( new HttpClient )->get( $url );
        } );
    }

    public function before( \Closure $callback ) {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    public function after( \Closure $callback ) {
        return $this->then( $callback );
    }

    public function then( \Closure $callback ) {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    public function onError( \Closure $callback ) {
        $this->errorCallbacks[] = $callback;

        return $this;
    }

    public function beforeCallbacks() {
        return $this->beforeCallbacks;
    }

    public function afterCallbacks() {
        return $this->afterCallbacks;
    }

    public function errorCallbacks() {
        return $this->errorCallbacks;
    }

    public function events( Array $events = null ) {
        if ( !is_null( $events ) ) {
            return $this->events = $events;
        }

        return $this->events;
    }

    public function dueEvents() {
        return array_filter( $this->events, function ( $event ) {
            return $event->isDue();
        } );
    }

    public function dismissEvent( $key ) {
        unset( $this->events[$key] );

        return $this;
    }
}