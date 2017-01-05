<?php

namespace Crunz\Configuration;

use Crunz\Singleton;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

class Configuration extends Singleton {
    protected $parameters = [];
    protected static $instance;
    protected function __construct() {
        $this->parameters = $this->process( $this->locateConfigFile() );
    }

    protected function process( $filename ) {
        $proc = new Processor();
        try {
            return $proc->processConfiguration( new Definition(), $this->parse( $filename ) );
        } catch ( InvalidConfigurationException $e ) {
            exit( $e->getMessage() );
        }
    }

    protected function parse( $filename ) {
        $conf   = [];
        $conf[] = Yaml::parse( file_get_contents( $filename ) );

        return $conf;
    }

    protected function locateConfigFile() {
        $config_file = getenv( 'CRUNZ_BASE_DIR' ) . '/crunz.yml';

        return file_exists( $config_file ) ? $config_file : __DIR__ . '/../../crunz.yml';
    }

    public function set( $key, $value ) {
        if ( is_null( $key ) ) {
            return $array = $value;
        }
        $keys = explode( '.', $key );
        while ( count( $keys ) > 1 ) {
            $key = array_shift( $keys );
            if ( !isset( $array[$key] ) || !is_array( $array[$key] ) ) {
                $array[$key] = [];
            }
            $array = &$array[$key];
        }
        $array[array_shift( $keys )] = $value;

        return $array;
    }

    public function has( $key ) {
        if ( !$array ) {
            return false;
        }

        if ( is_null( $key ) ) {
            return false;
        }

        if ( array_key_exists( $key, $this->parameters ) ) {
            return true;
        }
        $array = $this->parameters;
        foreach ( explode( '.', $key ) as $segment ) {
            if ( is_array( $array ) && array_key_exists( $key, $array ) ) {
                $array = $array[$segment];
            } else {
                return false;
            }
        }

        return true;
    }

    public function get( $key, $default = null ) {
        if ( array_key_exists( $key, $this->parameters ) ) {
            return $this->parameters[$key];
        }
        $array = $this->parameters;
        foreach ( explode( '.', $key ) as $segment ) {
            if ( is_array( $array ) && array_key_exists( $segment, $array ) ) {
                $array = $array[$segment];
            } else {
                return null;
            }
        }

        return $array;
    }

    public function all() {
        return $this->parameters;
    }
}