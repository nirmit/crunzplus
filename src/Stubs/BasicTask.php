<?php
use Crunz\Schedule;

$scheduler = new Schedule();
$scheduler->run('DummyCommand')
          ->description('DummyDescription')
          ->in('DummyPath')
          ->DummyFrequency()
          ->DummyConstraint()
          ->preventOverlapping();
return $scheduler;