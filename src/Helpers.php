<?php

use Crunz\Utils;

if ( !function_exists( 'split_camel' ) ) {
    function split_camel( $text ) {
        return Utils::splitCamel( $text );
    }
}

if ( !function_exists( 'word2number' ) ) {
    function word2number( $text ) {
        return Utils::wordToNumber( $text );
    }
}

if ( !function_exists( 'array_only' ) ) {
    function array_only( $array, $keys ) {
        return Utils::arrayOnly( $array, $keys );
    }
}

if ( !function_exists( 'setbase' ) ) {
    function setbase( $dir ) {
        return Utils::setBaseDir( $dir );
    }
}

if ( !function_exists( 'getbase' ) ) {
    function getbase() {
        return Utils::getBaseDir();
    }
}

if ( !function_exists( 'generate_path' ) ) {
    function generate_path( $relative_path ) {
        return Utils::generatePath( $relative_path );
    }
}

if ( !function_exists( 'getroot' ) ) {
    function getroot( $autoloader ) {
        return Utils::getRoot( $autoloader );
    }
}