<?php

namespace Crunz\Console;
use Symfony\Component\Console\Application as SymfonyApplication;

class CommandKernel extends SymfonyApplication {
    protected $commands = [
        \Crunz\Console\Command\ScheduleRunCommand::class,
        \Crunz\Console\Command\ScheduleListCommand::class,
        \Crunz\Console\Command\TaskGeneratorCommand::class,
        \Crunz\Console\Command\ConfigGeneratorCommand::class,
        \Crunz\Console\Command\ClosureRunCommand::class
    ];

    public function __construct( $appName, $appVersion ) {
        parent::__construct( $appName, $appVersion );
        foreach ( $this->commands as $command ) {
            $this->add( new $command );
        }
    }

    public function handle() {
        $this->run();
    }
}