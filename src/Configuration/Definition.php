<?php

namespace Crunz\Configuration;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Definition implements ConfigurationInterface {

    public function getConfigTreeBuilder() {
        $treeBuilder = new TreeBuilder();
        $rootNode    = $treeBuilder->root( 'crunz' );

        $rootNode
            ->children()

                ->scalarNode( 'source' )
                ->cannotBeEmpty()
                ->defaultValue( 'tasks' )
                ->info( 'path to the tasks directory' . PHP_EOL )
                ->end()

                ->scalarNode( 'suffix' )
                ->defaultValue( 'Tasks.php' )
                ->info( 'The suffix for filenames' . PHP_EOL )
                ->end()

                ->booleanNode( 'use_mysql' )
                ->defaultFalse()
                ->info( 'Use MySQL Task List' . PHP_EOL )
                ->end()

                ->scalarNode( 'server_id' )
                ->defaultValue( 'server1' )
                ->info( 'Server ID in a multi-server setup' . PHP_EOL )
                ->end()

                ->arrayNode( 'output' )

                    ->children()
                        ->booleanNode( 'log' )
                        ->defaultFalse()
                        ->info( 'Enable output logging' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'stream' )
                        ->defaultValue( 'file' )
                        ->info( 'Stream to log output' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'endpoint' )
                        ->defaultValue( '/dev/null' )
                        ->info( 'Endpoint to log output' . PHP_EOL )
                        ->end()

                        ->booleanNode( 'email' )
                        ->defaultFalse()
                        ->info( 'Enable email of output logs' . PHP_EOL )
                        ->end()
                    ->end()
                ->end()
                ->arrayNode( 'errors' )

                    ->children()
                        ->booleanNode( 'log' )
                        ->defaultFalse()
                        ->info( 'Enable error logging' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'stream' )
                        ->defaultValue( 'file' )
                        ->info( 'Stream to log errors' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'endpoint' )
                        ->defaultValue( '/dev/null' )
                        ->info( 'Endpoint to log errors' . PHP_EOL )
                        ->end()

                        ->booleanNode( 'email' )
                        ->defaultFalse()
                        ->info( 'Enable email of error logs' . PHP_EOL )
                        ->end()
                    ->end()
                ->end()
                ->arrayNode( 'mailer' )

                    ->children()
                        ->scalarNode( 'transport' )
                        ->info( 'The type the Swift transporter' . PHP_EOL )
                        ->end()

                        ->arrayNode( 'recipients' )
                        ->prototype( 'scalar' )->end()
                        ->info( 'List of the email recipients' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'sender_name' )
                        ->info( 'The sender name' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'sender_email' )
                        ->info( 'The sender email' . PHP_EOL )
                        ->end()
                    ->end()
                ->end()
                ->arrayNode( 'smtp' )
                    ->children()
                        ->scalarNode( 'host' )
                        ->info( 'SMTP host' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'port' )
                        ->info( 'SMTP port' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'username' )
                        ->info( 'SMTP username' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'password' )
                        ->info( 'SMTP password' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'encryption' )
                        ->info( 'SMTP encryption' . PHP_EOL )
                        ->end()
                    ->end()
                ->end()
                ->arrayNode( 'mysql' )
                    ->children()
                        ->scalarNode( 'host' )
                        ->defaultValue( 'localhost' )
                        ->info( 'MySQL host' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'port' )
                        ->defaultValue( 3306 )
                        ->info( 'MySQL port' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'username' )
                        ->info( 'MySQL username' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'password' )
                        ->info( 'MySQl password' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'dbname' )
                        ->info( 'MySQL Database Name' . PHP_EOL )
                        ->end()
                    ->end()
                ->end()
                ->arrayNode( 'mongodb' )
                    ->children()
                        ->scalarNode( 'host' )
                        ->defaultValue( 'localhost' )
                        ->info( 'MongoDB host' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'port' )
                        ->defaultValue( 27017 )
                        ->info( 'MongoDB port' . PHP_EOL )
                        ->end()

                        ->scalarNode( 'dbname' )
                        ->defaultValue( 'crunz' )
                        ->info( 'MongoDB Database Name' . PHP_EOL )
                        ->end()
                    ->end()
                ->end()
            ->end();
        return $treeBuilder;
    }
}