<?php

namespace Console\Games;

use Bootgly\ACI\Tests\Suite;

return new Suite(
   // * Config
   autoBoot: __DIR__,
   autoInstance: true,
   autoReport: true,
   autoSummarize: true,
   exitOnFailure: true,
   // * Data
   suiteName: __NAMESPACE__,
   tests: [
      '1.1-canvas-diff-flush',
      '1.2-canvas-modes',
      '2.1-keyboard-state',
      '3.1-loop-ticks',
      '4.1-scenes-machine',
      '5.1-sprite-frames-stamp',
      '6.1-sprites-sheet',
      '7.1-timer-cadence',
      '8.1-vector-ops',
      '9.1-zone-collision',
   ]
);
