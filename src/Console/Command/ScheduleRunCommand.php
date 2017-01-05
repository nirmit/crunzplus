<?php

namespace Crunz\Console\Command;

use Crunz\Configuration\Configurable;
use Crunz\EventRunner;
use Crunz\Schedule;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ScheduleRunCommand extends Command {
    use Configurable;
    protected $runningEvents = [];

    protected function configure() {
        $this->configurable();

        $this->setName( 'schedule:run' )
            ->setDescription( 'Starts the event runner.' )
            ->setDefinition( [
                new InputArgument( 'source', InputArgument::OPTIONAL, 'The source directory.', generate_path( $this->config( 'source' ) ) )
            ] )
            ->setHelp( 'This command starts the Crunz event runner.' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $this->arguments = $input->getArguments();
        $this->options   = $input->getOptions();
        $files           = $this->collectFiles( $this->arguments['source'] );

        if ( !count( $files ) ) {
            $output->writeln( '<comment>No task found! Please check your source path.</comment>' );
            exit();
        }
        $schedules = [];

        foreach ( $files as $file ) {
            $schedule = require $file->getRealPath();
            if ( !$schedule instanceof Schedule ) {
                continue;
            }
            $schedule->events( $schedule->dueEvents() );
            if ( count( $schedule->events() ) ) {
                $schedules[] = $schedule;
            }
        }

        if ( !count( $schedules ) ) {
            $output->writeln( '<comment>No event is due!</comment>' );
            exit();
        }

        // Running the events
        ( new EventRunner() )->handle( $schedules );
    }

    protected function collectFiles( $source ) {
        if ( !file_exists( $source ) ) {
            return [];
        }
        $finder   = new Finder();
        $iterator = $finder->files()->name( '*' . $this->config( 'suffix' ) )->in( $source );

        return $iterator;
    }
}