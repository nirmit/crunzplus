<?php

namespace Crunz\Console\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConfigGeneratorCommand extends Command {
    protected function configure() {
        $this->setName( 'publish:config' )
            ->setDescription( 'Generates a config file within the project\'s root directory.' )
            ->setHelp( 'This generates a config file in YML format within the project\'s root directory.' );
    }

    protected function execute( InputInterface $input, OutputInterface $output ) {
        $filename = getbase() . '/crunz.yml';
        if ( file_exists( $filename ) ) {
            $output->writeln( '<comment>The configuration file already exists.</comment>' );
            exit();
        }
        $src = __DIR__ . '/../../../crunz.yml';
        if ( copy( $src, $filename ) ) {

            $output->writeln( '<info>The configuration file was generated successfully.</info>' );
            exit();
        }
        $output->writeln( '<comment>There was a problem when generating the file.</comment>' );
        exit();
    }
}