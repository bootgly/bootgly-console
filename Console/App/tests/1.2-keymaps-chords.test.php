<?php

namespace Console\App;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should buffer chord prefixes and expire them after the timeout',
   test: function () {
      // ! Keymaps with a chord binding
      $Keymaps = new Keymaps;

      $ran = 0;
      $Keymaps->bind(['g', 'g'], 'Go to top', function () use (&$ran): void {
         $ran++;
      });
      $quits = 0;
      $Keymaps->bind('q', 'Quit', function () use (&$quits): void {
         $quits++;
      });

      // @ Chord: first key buffers, second key runs
      yield assert(
         assertion: $Keymaps->handle('g', at: 1.0) === true && $ran === 0,
         description: 'Chord prefix is buffered (consumed, not run)'
      );
      yield assert(
         assertion: $Keymaps->handle('g', at: 1.1) === true && $ran === 1,
         description: 'Chord completion runs the handler'
      );

      // @ Chord timeout: pending prefix expires
      $Keymaps->handle('g', at: 2.0);
      yield assert(
         assertion: $Keymaps->handle('g', at: 3.0) === true && $ran === 1,
         description: 'Expired prefix does not complete the chord (re-buffers)'
      );
      yield assert(
         assertion: $Keymaps->handle('g', at: 3.1) === true && $ran === 2,
         description: 'Fresh chord completes after the expired one'
      );

      // @ Broken chord: the breaking key retries as a fresh sequence
      $Keymaps->handle('g', at: 4.0);
      yield assert(
         assertion: $Keymaps->handle('q', at: 4.1) === true && $quits === 1,
         description: 'A key breaking a chord is retried as a fresh binding'
      );

      // @ reset() clears the pending buffer
      $Keymaps->handle('g', at: 5.0);
      $Keymaps->reset();
      yield assert(
         assertion: $Keymaps->handle('g', at: 5.1) === true && $ran === 2,
         description: 'reset() drops the pending chord'
      );
   }
);
