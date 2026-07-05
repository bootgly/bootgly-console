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
   ]
);
