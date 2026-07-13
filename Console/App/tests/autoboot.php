<?php

namespace Console\App;

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
      '1.1-keymaps-bind-handle',
      '1.2-keymaps-chords',
      '2.1-router-load-resolve',
      '3.1-screens-stack',
      '4.1-statusbar-render',
      '5.1-toasts-queue-render',
      '6.1-palette-control-filter',
      '7.1-app-render-compose',
      '8.1-tail-follow-pull',
   ]
);
