<?php

namespace Console\Games;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should fire on interval, carry remainders and support one-shot cooldowns',
   test: function () {
      // ! Repeating timer (0.5s cycle)
      $March = new Timer(0.5);

      // @ Fires only when the cycle completes
      yield assert(
         assertion: $March->tick(0.2) === false && $March->tick(0.3) === true,
         description: 'Repeating timer fires when the accumulated delta reaches the interval'
      );

      // @ Carries the remainder across cycles
      $March->reset();
      yield assert(
         assertion: $March->tick(0.6) === true && $March->elapsed > 0.09 && $March->elapsed < 0.11,
         description: 'Repeating timer carries the overshoot into the next cycle'
      );

      // @ Interval is mutable mid-flight (cadence acceleration)
      $March->reset();
      $March->tick(0.15);
      $March->interval = 0.2;
      yield assert(
         assertion: $March->tick(0.05) === true,
         description: 'Shrinking the interval mid-cycle makes the timer fire sooner'
      );

      // ! One-shot timer (0.3s cooldown)
      $Fire = new Timer(0.3, repeat: false);

      // @ Fires once, then stays expired
      yield assert(
         assertion: $Fire->tick(0.1) === false && $Fire->tick(0.2) === true,
         description: 'One-shot timer fires when the interval completes'
      );
      yield assert(
         assertion: $Fire->expired === true && $Fire->tick(1.0) === false,
         description: 'Expired one-shot timer never fires again'
      );

      // @ reset() rearms
      $Fire->reset();
      yield assert(
         assertion: $Fire->expired === false && $Fire->tick(0.3) === true,
         description: 'reset() rearms the one-shot timer'
      );
   }
);
