<?php
namespace Crunz;

use Carbon\Carbon;
use Closure;
use Cron\CronExpression;
use GuzzleHttp\Client as HttpClient;
use SuperClosure\Serializer;
use Symfony\Component\Process\Process;

class Event {
    protected $id;
    protected $command;
    protected $process;
    protected $expression = '* * * * * *';
    protected $timezone;
    protected $user;
    protected $filters         = [];
    protected $rejects         = [];
    protected $beforeCallbacks = [];
    protected $afterCallbacks  = [];
    protected $cwd;
    protected $fieldsPosition = [
        'minute' => 1,
        'hour'   => 2,
        'day'    => 3,
        'month'  => 4,
        'week'   => 5
    ];
    public $preventOverlapping = false;
    public $output             = '/dev/null';
    public $shouldAppendOutput = false;
    public $description;
    public $outputStream;
    public $logger;

    public function __construct( $id, $command ) {
        $this->command = $command;
        $this->id      = $id;
        $this->output  = $this->getDefaultOutput();
    }

    protected function getDefaultOutput() {
        return ( DIRECTORY_SEPARATOR == '\\' ) ? 'NUL' : '/dev/null';
    }

    public function in( $directory ) {
        $this->cwd = $directory;

        return $this;
    }

    public function nullOutput() {
        return $this->output == 'NUL' || $this->output == '/dev/null';
    }

    public function buildCommand() {
        $command = '';
        if ( $this->cwd ) {
            if ( $this->user ) {
                $command .= $this->sudo();
            }
            $command .= 'cd ' . $this->cwd . '; ';
        }
        if ( $this->user ) {
            $this->sudo();
        }
        $command .= $this->isClosure() ? $this->serializeClosure( $this->command ) : $this->command;

        return trim( $command, '& ' );
    }

    protected function sudo( $user ) {
        return 'sudo -u' . $user . ' ';
    }

    public function isClosure() {
        return is_object( $this->command ) && ( $this->command instanceof Closure );
    }

    protected function serializeClosure( $closure ) {
        $closure = ( new Serializer() )->serialize( $closure );

        return __DIR__ . '/../crunz closure:run ' . http_build_query( [$closure] );
    }

    public function isDue() {
        return $this->expressionPasses() && $this->filtersPass();
    }

    protected function expressionPasses() {
        $date = Carbon::now();
        if ( $this->timezone ) {
            $date->setTimezone( $this->timezone );
        }

        return CronExpression::factory( $this->expression )->isDue( $date->toDateTimeString() );
    }

    public function filtersPass() {
        $invoker = new Invoker();
        foreach ( $this->filters as $callback ) {
            if ( !$invoker->call( $callback ) ) {
                return false;
            }
        }

        foreach ( $this->rejects as $callback ) {
            if ( $invoker->call( $callback ) ) {
                return false;
            }
        }

        return true;
    }

    public function start() {
        $this->setProcess( new Process( $this->buildCommand() ) );
        $this->getProcess()->start();
        if ( $this->preventOverlapping ) {
            $this->lock();
        }

        return $this->getProcess()->getPid();
    }

    public function cron( $expression ) {
        $this->expression = $expression;

        return $this;
    }

    public function hourly() {
        return $this->cron( '0 * * * * *' );
    }

    public function daily() {
        return $this->cron( '0 0 * * * *' );
    }

    public function on( $date ) {
        $date     = date_parse( $date );
        $segments = array_only( $date, array_flip( $this->fieldsPosition ) );
        if ( $date['year'] ) {
            $this->skip( function () use ( $segments ) {
                return (int) date( 'Y' ) != $segments['year'];
            } );
        }

        foreach ( $segments as $key => $value ) {
            if ( $value != false ) {
                $this->spliceIntoPosition( $this->fieldsPosition[$key], (int) $value );
            }
        }

        return $this;
    }

    public function at( $time ) {
        return $this->dailyAt( $time );
    }

    public function dailyAt( $time ) {
        $segments = explode( ':', $time );

        return $this->spliceIntoPosition( 2, (int) $segments[0] )->spliceIntoPosition( 1, count( $segments ) > 1 ? (int) $segments[1] : '0' );
    }

    public function between( $from, $to ) {
        return $this->from( $from )->to( $to );
    }

    public function from( $datetime ) {
        return $this->skip( function () use ( $datetime ) {
            return $this->notYet( $datetime );
        } );
    }

    public function to( $datetime ) {
        return $this->skip( function () use ( $datetime ) {
            return $this->past( $datetime );
        } );
    }

    protected function notYet( $datetime ) {
        return time() < strtotime( $datetime );
    }

    protected function past( $datetime ) {
        return time() > strtotime( $datetime );
    }

    public function twiceDaily( $first = 1, $second = 13 ) {
        $hours = $first . ',' . $second;

        return $this->spliceIntoPosition( 1, 0 )->spliceIntoPosition( 2, $hours );
    }

    public function weekdays() {
        return $this->spliceIntoPosition( 5, '1-5' );
    }

    public function mondays() {
        return $this->days( 1 );
    }

    public function tuesdays() {
        return $this->days( 2 );
    }

    public function wednesdays() {
        return $this->days( 3 );
    }

    public function thursdays() {
        return $this->days( 4 );
    }

    public function fridays() {
        return $this->days( 5 );
    }

    public function saturdays() {
        return $this->days( 6 );
    }

    public function sundays() {
        return $this->days( 0 );
    }

    public function weekly() {
        return $this->cron( '0 0 * * 0 *' );
    }

    public function weeklyOn( $day, $time = '0:0' ) {
        $this->dailyAt( $time );

        return $this->spliceIntoPosition( 5, $day );
    }

    public function monthly() {
        return $this->cron( '0 0 1 * * *' );
    }

    public function quarterly() {
        return $this->cron( '0 0 1 */3 * *' );
    }

    public function yearly() {
        return $this->cron( '0 0 1 1 * *' );
    }

    public function days( $days ) {
        $days = is_array( $days ) ? $days : func_get_args();

        return $this->spliceIntoPosition( 5, implode( ',', $days ) );
    }

    public function hour( $value ) {
        $value = is_array( $value ) ? $value : func_get_args();

        return $this->spliceIntoPosition( 2, implode( ',', $value ) );
    }

    public function minute( $value ) {
        $value = is_array( $value ) ? $value : func_get_args();

        return $this->spliceIntoPosition( 1, implode( ',', $value ) );
    }

    public function dayOfMonth( $value ) {
        $value = is_array( $value ) ? $value : func_get_args();

        return $this->spliceIntoPosition( 3, implode( ',', $value ) );
    }

    public function month( $value ) {
        $value = is_array( $value ) ? $value : func_get_args();

        return $this->spliceIntoPosition( 4, implode( ',', $value ) );
    }

    public function dayOfWeek( $value ) {
        $value = is_array( $value ) ? $value : func_get_args();

        return $this->spliceIntoPosition( 5, implode( ',', $value ) );
    }

    public function timezone( $timezone ) {
        $this->timezone = $timezone;

        return $this;
    }

    public function user( $user ) {
        $this->user = $user;

        return $this;
    }

    public function preventOverlapping() {
        $this->preventOverlapping = true;
        $this->skip( function () {
            return $this->isLocked();
        } );
        $this->after( function () {
            $lockfile = $this->lockFile();
            if ( file_exists( $lockfile ) ) {
                unlink( $lockfile );
            }
        } );

        return $this;
    }

    public function when( Closure $callback ) {
        $this->filters[] = $callback;

        return $this;
    }

    public function skip( Closure $callback ) {
        $this->rejects[] = $callback;

        return $this;
    }

    public function sendOutputTo( $location, $append = false ) {
        $this->output             = $location;
        $this->shouldAppendOutput = $append;

        return $this;
    }

    public function appendOutputTo( $location ) {
        return $this->sendOutputTo( $location, true );
    }

    public function pingBefore( $url ) {
        return $this->before( function () use ( $url ) {
            ( new HttpClient )->get( $url );
        } );
    }

    public function before( \Closure $callback ) {
        $this->beforeCallbacks[] = $callback;

        return $this;
    }

    public function thenPing( $url ) {
        return $this->then( function () use ( $url ) {
            ( new HttpClient )->get( $url );
        } );
    }

    public function after( Closure $callback ) {
        return $this->then( $callback );
    }

    public function then( Closure $callback ) {
        $this->afterCallbacks[] = $callback;

        return $this;
    }

    public function name( $description ) {
        return $this->description( $description );
    }

    public function setProcess( \Symfony\Component\Process\Process $process = null ) {
        $this->process = $process;

        return $this;
    }

    public function getProcess() {
        return $this->process;
    }

    public function description( $description ) {
        $this->description = $description;

        return $this;
    }

    protected function spliceIntoPosition( $position, $value ) {
        $segments = explode( ' ', $this->expression );

        $segments[$position - 1] = $value;

        return $this->cron( implode( ' ', $segments ) );
    }

    public function every( $unit = null, $value = null ) {
        if ( !isset( $this->fieldsPosition[$unit] ) ) {
            return $this;
        }
        $value = $value == 1 ? '*' : '*/' . $value;

        return $this->spliceIntoPosition( $this->fieldsPosition[$unit], $value )->applyMask( $unit );
    }

    public function getId() {
        return $this->id;
    }

    public function getSummaryForDisplay() {
        if ( is_string( $this->description ) ) {
            return $this->description;
        }

        return $this->buildCommand();
    }

    public function getCommandForDisplay() {
        return $this->isClosure() ? 'object(Closure)' : $this->buildCommand();
    }

    public function getExpression() {
        return $this->expression;
    }

    public function setCommand( $command ) {
        $this->command = $command;

        return $this;
    }

    public function getCommand() {
        return $this->command;
    }

    public function getWorkingDirectory() {
        return $this->cwd;
    }

    public function getOutputStream() {
        return $this->outputStream;
    }

    public function beforeCallbacks() {
        return $this->beforeCallbacks;
    }

    public function afterCallbacks() {
        return $this->afterCallbacks;
    }

    protected function applyMask( $unit ) {
        $cron = explode( ' ', $this->expression );
        $mask = ['0', '0', '1', '1', '*', '*'];
        $fpos = $this->fieldsPosition[$unit] - 1;

        array_splice( $cron, 0, $fpos, array_slice( $mask, 0, $fpos ) );

        return $this->cron( implode( ' ', $cron ) );
    }

    protected function lock() {
        file_put_contents( $this->lockFile(), $this->process->getPid() );
    }

    public function isLocked() {
        $pid = $this->lastPid();

        return ( !is_null( $pid ) && posix_getsid( $pid ) ) ? true : false;
    }

    public function lastPid() {
        $lock_file = $this->lockFile();

        return file_exists( $lock_file ) ? (int) trim( file_get_contents( $lock_file ) ) : null;
    }

    public function lockFile() {
        return rtrim( sys_get_temp_dir(), '/' ) . '/crunz-' . md5( $this->buildCommand() );
    }

    public function __call( $methodName, $params ) {
        preg_match( '/^every([A-Z][a-zA-Z]+)?(Minute|Hour|Day|Month)s?$/', $methodName, $matches );
        if ( !count( $matches ) || $matches[1] == 'Zero' ) {
            throw new \BadMethodCallException();
        }

        $amount = !empty( $matches[1] ) ? word2number( split_camel( $matches[1] ) ) : 1;

        if ( !$amount ) {
            throw new \BadMethodCallException();
        }

        return $this->every( strtolower( $matches[2] ), $amount );
    }
}