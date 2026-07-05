<?php

namespace Console\Games;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should run fixed-timestep ticks over a scripted channel and stop on limit or close',
   test: function () {
      // ! Loop at a high tick rate with a tick limit (deterministic, fast)
      $Keyboard = new Keyboard;
      $Loop = new Loop($Keyboard);
      $Loop->tps = 1000;
      $Loop->limit = 5;

      // ! Scripted channel: two tokens, then endless timeouts
      $reading = function (int $length = 1024, null|int $timeout = null) {
         yield "UP\nRIGHT\n";

         while (true) {
            yield null;
         }
      };

      $ticks = 0;
      $frames = 0;

      // @
      $Loop->run(
         $reading,
         update: function (float $delta) use (&$ticks): void {
            $ticks++;
         },
         render: function () use (&$frames): void {
            $frames++;
         }
      );

      // @ Valid
      yield assert(
         assertion: $Loop->ticks === 5 && $ticks === 5,
         description: "Loop stops at the tick limit: {$Loop->ticks}"
      );
      yield assert(
         assertion: $Loop->running === false,
         description: 'Loop is stopped after the limit'
      );
      yield assert(
         assertion: $frames > 0,
         description: "Render ran per burst: {$frames} frames"
      );
      yield assert(
         assertion: $Keyboard->pop('UP') === true && $Keyboard->pop('RIGHT') === true,
         description: 'Channel tokens fed the Keyboard'
      );

      // @ Closed channel stops the loop
      $Loop2 = new Loop($Keyboard);
      $Loop2->tps = 1000;

      $closed = function (int $length = 1024, null|int $timeout = null) {
         yield false;
      };

      $Loop2->run(
         $closed,
         update: static function (float $delta): void {},
         render: static function (): void {}
      );

      yield assert(
         assertion: $Loop2->running === false && $Loop2->ticks === 0,
         description: 'A closed channel stops the loop without ticking'
      );
   }
);
