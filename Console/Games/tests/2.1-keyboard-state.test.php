<?php

namespace Console\Games;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should track pressed (edge) and held (repeat-window) key states',
   test: function () {
      // ! Keyboard with an injected clock (grace 0.6s, window 0.15s)
      $Keyboard = new Keyboard;

      // @ Edge-triggered presses queue and are consumed once
      $Keyboard->press('UP', at: 1.0);
      yield assert(
         assertion: $Keyboard->pop('UP') === true && $Keyboard->pop('UP') === false,
         description: 'pop() consumes exactly one queued press'
      );

      // @ Held inside the grace window (first press → first repeat)
      yield assert(
         assertion: $Keyboard->check('UP', at: 1.5) === true,
         description: 'Key is held inside the grace window'
      );
      yield assert(
         assertion: $Keyboard->check('UP', at: 1.7) === false,
         description: 'Key is released after the grace window'
      );

      // @ Auto-repeats tighten the window
      $Keyboard->press('UP', at: 2.0);
      $Keyboard->press('UP', at: 2.05); // repeat
      yield assert(
         assertion: $Keyboard->check('UP', at: 2.15) === true,
         description: 'Key is held between auto-repeats'
      );
      yield assert(
         assertion: $Keyboard->check('UP', at: 2.5) === false,
         description: 'Key is released when repeats stop'
      );

      // @ expire() drops stale hold states
      $Keyboard->press('DOWN', at: 3.0);
      $Keyboard->expire(at: 4.0);
      yield assert(
         assertion: $Keyboard->check('DOWN', at: 4.0) === false,
         description: 'expire() drops stale keys'
      );

      // @ reset() clears everything
      $Keyboard->press('LEFT', at: 5.0);
      $Keyboard->reset();
      yield assert(
         assertion: $Keyboard->pop('LEFT') === false && $Keyboard->check('LEFT', at: 5.0) === false,
         description: 'reset() clears pressed and held state'
      );
   }
);
