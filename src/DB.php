<?php

namespace Crunz;

use Crunz\Configuration\Configurable;

class DB {
    use Configurable;

    public $db = null;
    public $conf;
    public $useMysqlTasks = false;
    public function __construct() {
        $this->configurable();

        $this->conf = $this->config;

        if( !$this->config( 'use_mysql') ) return false;
        $this->db = new \mysqli(
            $this->config( 'mysql.host' ),
            $this->config( 'mysql.username' ),
            $this->config( 'mysql.password' ),
            $this->config( 'mysql.dbname' ),
            $this->config( 'mysql.port' )
        );
    }
}