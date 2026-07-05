<?php

namespace Console\App;

use function assert;
use function count;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should navigate screens through the stack (switch / push / pop)',
   test: function () {
      // ! Screens with the fixture manifest
      $Screens = new Screens;
      $Screens->load(__DIR__ . '/screens');

      // @ Empty stack
      yield assert(
         assertion: $Screens->Current === null,
         description: 'Current is null on an empty stack'
      );

      // @ switch() starts the stack
      $Dashboard = $Screens->switch('Dashboard', ['tag' => 'first']);
      yield assert(
         assertion: $Screens->Current === $Dashboard && $Dashboard->state === ['tag' => 'first'],
         description: 'switch() activates the screen with its state'
      );

      // @ push() overlays
      $Logs = $Screens->push('Logs');
      yield assert(
         assertion: $Screens->Current === $Logs && count($Screens->Stack) === 2,
         description: 'push() overlays a new screen'
      );

      // @ pop() returns to the previous screen
      $popped = $Screens->pop();
      yield assert(
         assertion: $popped === $Logs && $Screens->Current === $Dashboard,
         description: 'pop() returns to the previous screen'
      );

      // @ switch() replaces the top
      $Screens->switch('Logs');
      yield assert(
         assertion: $Screens->Current?->name === 'Logs' && count($Screens->Stack) === 1,
         description: 'switch() replaces the current screen'
      );
   }
);
