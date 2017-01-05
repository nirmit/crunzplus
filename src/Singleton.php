<?php

namespace Crunz;

class Singleton {
    protected static $instance = null;

    public static function getInstance() {
        if ( is_null( static::$instance ) ) {
            return new static();
        }

        return static::$instance;
    }

    private function __clone() {}

    private function __wakeup() {}
}