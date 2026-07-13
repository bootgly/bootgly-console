<?php

namespace Console;

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
      '1.1-console-autoboot',
      '2.1-games-client-tokens',
      '2.2-games-canvas-render',
   ]
);
