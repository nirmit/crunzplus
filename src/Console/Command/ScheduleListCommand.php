<?php

namespace Crunz\Console\Command;

use Crunz\Configuration\Configurable;
use Crunz\Schedule;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

class ScheduleListCommand extends Command {
    use Configurable;

    protected function configure() {
        $this->configurable();

        $this->setName( 'schedule:list' )
            ->setDescription( 'Displays the list of scheduled tasks.' )
            ->setDefinition( [
                new InputArgument( 'source', InputArgument::OPTIONAL, 'The source directory for collecting the tasks.', generate_path( $this->config( 'source' ) ) )
            ] )
            ->setHelp( 'This command displays the scheduled tasks in a tabular format.' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $this->options   = $input->getOptions();
        $this->arguments = $input->getArguments();
        $task_files      = $this->collectTaskFiles( $this->arguments['source'] );

        if ( !count( $task_files ) ) {
            $output->writeln( '<comment>No task found!</comment>' );
            exit();
        }
        $table = new Table( $output );
        $table->setHeaders( ['#', 'Task', 'Expression', 'Command to Run'] );
        $row = 0;
        foreach ( $task_files as $key => $taskFile ) {
            $schedule = require $taskFile->getRealPath();
            if ( !$schedule instanceof Schedule ) {
                continue;
            }
            $events = $schedule->events();
            foreach ( $events as $event ) {
                $table->addRow( [++$row, $event->description, $event->getExpression(), $event->getCommandForDisplay()] );
            }
        }
        $table->render();
    }

    public function collectTaskFiles( $source ) {
        if ( !file_exists( $source ) ) {
            return [];
        }
        $finder   = new Finder();
        $iterator = $finder->files()->name( '*' . $this->config( 'suffix' ) )->in( $source );

        return $iterator;
    }
}