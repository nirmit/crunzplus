<?php

namespace Crunz\Configuration;
use Crunz\Configuration\Configuration;

class ConfigurationFactory {
    public static function makeOne() {
        return Configuration::getInstance();
    }
}