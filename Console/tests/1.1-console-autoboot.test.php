<?php

// Global namespace: the `Console` bootable (class + constant) lives in the
// global namespace — a namespaced test could not reference it unqualified.

use function assert;
use function class_exists;
use function defined;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Console\App;


return new Specification(
   description: 'It should autoboot the Console platform (constants + bootable + autoloader)',
   test: function () {
      // @ Constants
      yield assert(
         assertion: defined('CONSOLE_ROOT_BASE') === true,
         description: 'CONSOLE_ROOT_BASE is defined'
      );
      yield assert(
         assertion: defined('CONSOLE_VERSION') === true,
         description: 'CONSOLE_VERSION is defined: ' . CONSOLE_VERSION
      );

      // @ Bootable singleton
      yield assert(
         assertion: Console instanceof Console,
         description: 'The Console constant holds the Console bootable'
      );

      // @ Double boot guarded
      $guarded = false;
      try {
         Console->autoboot();
      }
      catch (Exception) {
         $guarded = true;
      }

      yield assert(
         assertion: $guarded === true,
         description: 'Rebooting the Console platform throws'
      );

      // @ Autoloader resolves platform entities
      yield assert(
         assertion: class_exists(App::class) === true,
         description: 'The autoloader resolves Console\App'
      );
   }
);
