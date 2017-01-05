<?php

namespace Crunz\Console\Command;

use Crunz\Configuration\Configurable;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class TaskGeneratorCommand extends Command {
    use Configurable;
    protected $stub;
    protected $defaults = [
        'frequency'   => 'everyThirtyMinutes',
        'constraint'  => 'weekdays',
        'in'          => 'path/to/your/command',
        'run'         => 'command/to/execute',
        'description' => 'Task description',
        'type'        => 'basic'
    ];

    protected function configure() {
        $this->configurable();
        $this->setName( 'make:task' )
            ->setDescription( 'Generates a task file with one task.' )
            ->setDefinition( [
                new InputArgument( 'taskfile', InputArgument::REQUIRED, 'The task file name' ),
                new InputOption( 'frequency', 'f', InputOption::VALUE_OPTIONAL, 'Task frequency', $this->defaults['frequency'] ),
                new InputOption( 'constraint', 'c', InputOption::VALUE_OPTIONAL, 'Task constraint', $this->defaults['constraint'] ),
                new InputOption( 'in', 'i', InputOption::VALUE_OPTIONAL, 'Command path', $this->defaults['in'] ),
                new InputOption( 'run', 'r', InputOption::VALUE_OPTIONAL, 'Task command', $this->defaults['run'] ),
                new InputOption( 'description', 'd', InputOption::VALUE_OPTIONAL, 'Task description', $this->defaults['description'] ),
                new InputOption( 'type', 't', InputOption::VALUE_OPTIONAL, 'Task type', $this->defaults['type'] )
            ] )
            ->setHelp( 'This command makes a task file skeleton.' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $this->input     = $input;
        $this->output    = $output;
        $this->arguments = $input->getArguments();
        $this->options   = $input->getOptions();
        $this->stub      = $this->getStub();

        if ( $this->stub ) {
            $this->replaceFrequency()->replaceConstraint()->replaceCommand()->replacePath()->replaceDescription();
        }

        if ( $this->save() ) {
            $output->writeln( '<info>The task file generated successfully</info>' );
        } else {
            $output->writeln( '<comment>There was a problem when generating the file. Please check your command.</comment>' );
        }
        exit();
    }

    protected function save() {
        return file_put_contents( $this->outputPath() . '/' . $this->outputFile(), $this->stub );
    }

    protected function ask( $question ) {
        $helper   = $this->getHelper( 'question' );
        $question = new Question( "<question>{$question}</question>" );

        return $helper->ask( $this->input, $this->output, $question );
    }

    protected function outputPath() {
        $destination = $this->ask( 'Where do you want to save the file? (Press enter for the current directory)' );
        $output_path = !is_null( $destination ) ? $destination : generate_path( $this->config( 'source' ) );

        if ( !file_exists( $output_path ) ) {
            mkdir( $output_path, 0744, true );
        }

        return $output_path;
    }

    protected function outputFile() {
        return preg_replace( '/Tasks|\.php$/', '', $this->arguments['taskfile'] ) . $this->config( 'suffix' );
    }

    protected function getStub() {
        return file_get_contents( __DIR__ . '/../../Stubs/' . ucfirst( $this->type() . 'Task.php' ) );
    }

    protected function type() {
        return $this->options['type'];
    }

    protected function replaceFrequency() {
        $this->stub = str_replace( 'DummyFrequency', rtrim( $this->options['frequency'], '()' ), $this->stub );

        return $this;
    }

    protected function replaceConstraint() {
        $this->stub = str_replace( 'DummyConstraint', rtrim( $this->options['constraint'], '()' ), $this->stub );

        return $this;
    }

    protected function replaceCommand() {
        $this->stub = str_replace( 'DummyCommand', $this->options['run'], $this->stub );

        return $this;
    }

    protected function replacePath() {
        $this->stub = str_replace( 'DummyPath', $this->options['in'], $this->stub );

        return $this;
    }

    protected function replaceDescription() {
        $this->stub = str_replace( 'DummyDescription', $this->options['description'], $this->stub );

        return $this;
    }
}