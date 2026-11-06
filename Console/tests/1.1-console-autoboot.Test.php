<?php

// Global namespace: the `Console` bootable (class + constant) lives in the
// global namespace — a namespaced test could not reference it unqualified.


use Bootgly\ACI\Tests\Suite\Test;
use Bootgly\API\Platform;
use Console\App;


return new Test(
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
      yield assert(
         assertion: Console instanceof Platform,
         description: 'The Console bootable implements the Bootgly\API\Platform contract'
      );

      // @ Included after Bootgly (Composer's `files` order): the root is mounted all the same
      $Process = proc_open(
         [PHP_BINARY, '-r', 'require $argv[1]; require $argv[2]; echo json_encode(defined("Console") && Console instanceof Bootgly\API\Platform);', '--', BOOTGLY_ROOT_DIR . 'autoboot.php', CONSOLE_ROOT_DIR . 'autoboot.php'],
         [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
         $Pipes
      );
      $late = (string) stream_get_contents($Pipes[1]);
      fclose($Pipes[1]);
      fclose($Pipes[2]);
      proc_close($Process);

      yield assert(
         assertion: $late === 'true',
         description: "The Console root is mounted when its autoboot.php runs after Bootgly's (got '{$late}')"
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
