<?php

namespace Crunz\Configuration;

use Crunz\Configuration\ConfigurationFactory as ConfigFactory;

trait Configurable {
    protected $config = null;
    protected function configurable() {
        $this->config = ConfigFactory::makeOne();
    }

    protected function config( $key ) {
        if ( is_null( $this->config ) ) {
            return;
        }

        return $this->config->get( $key );
    }
}