<?php

namespace Console\Games;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should mutate in place, chain, integrate with a factor and compute length',
   test: function () {
      // ! Vectors
      $Position = new Vector(10.0, 5.0);
      $Velocity = new Vector(4.0, -2.0);

      // @ add() sums components
      $Position->add($Velocity);
      yield assert(
         assertion: $Position->x === 14.0 && $Position->y === 3.0,
         description: 'add() sums the components in place'
      );

      // @ add() with a factor integrates (Euler step)
      $Position->add($Velocity, 0.5);
      yield assert(
         assertion: $Position->x === 16.0 && $Position->y === 2.0,
         description: 'add() with a factor scales the added vector (integration step)'
      );

      // @ scale() multiplies both components
      $Velocity->scale(-1.0);
      yield assert(
         assertion: $Velocity->x === -4.0 && $Velocity->y === 2.0,
         description: 'scale(-1.0) reverses the vector'
      );

      // @ Operations chain on the same instance
      $Chained = $Position->add($Velocity, 0.0)->scale(1.0);
      yield assert(
         assertion: $Chained === $Position,
         description: 'Operations return the same instance for chaining'
      );

      // @ length hook
      $Size = new Vector(3.0, 4.0);
      yield assert(
         assertion: $Size->length === 5.0,
         description: 'length computes the Euclidean length'
      );

      // @ clone gives an independent copy
      $Copy = clone $Size;
      $Copy->scale(2.0);
      yield assert(
         assertion: $Size->x === 3.0 && $Copy->x === 6.0,
         description: 'clone produces an independent vector'
      );
   }
);
