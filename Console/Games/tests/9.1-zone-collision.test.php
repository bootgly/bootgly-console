<?php

namespace Console\Games;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should test AABB overlap (strict), point containment (inclusive) and clamp points',
   test: function () {
      // ! Zones
      $Field = new Zone(0.0, 0.0, 10.0, 10.0);

      // @ check() — overlapping zones collide
      yield assert(
         assertion: $Field->check(new Zone(5.0, 5.0, 10.0, 10.0)) === true,
         description: 'Overlapping zones collide'
      );

      // @ check() — disjoint zones do not
      yield assert(
         assertion: $Field->check(new Zone(20.0, 20.0, 5.0, 5.0)) === false,
         description: 'Disjoint zones do not collide'
      );

      // @ check() — strict edges: touching zones do not collide
      yield assert(
         assertion: $Field->check(new Zone(10.0, 0.0, 5.0, 5.0)) === false,
         description: 'Edge-touching zones do not collide (strict edges)'
      );

      // @ contain() — inside, edge (inclusive) and outside
      yield assert(
         assertion: $Field->contain(new Vector(5.0, 5.0)) === true,
         description: 'Point inside the zone is contained'
      );
      yield assert(
         assertion: $Field->contain(new Vector(10.0, 10.0)) === true,
         description: 'Point on the edge is contained (inclusive edges)'
      );
      yield assert(
         assertion: $Field->contain(new Vector(10.5, 5.0)) === false,
         description: 'Point outside the zone is not contained'
      );

      // @ clamp() — mutates the point into bounds and returns the same instance
      $Point = new Vector(-3.0, 15.0);
      $Clamped = $Field->clamp($Point);
      yield assert(
         assertion: $Clamped === $Point && $Point->x === 0.0 && $Point->y === 10.0,
         description: 'clamp() pins the point into the zone and returns the same Vector'
      );

      // @ clamp() — points already inside are untouched
      $Inside = new Vector(4.0, 6.0);
      $Field->clamp($Inside);
      yield assert(
         assertion: $Inside->x === 4.0 && $Inside->y === 6.0,
         description: 'clamp() leaves points already inside untouched'
      );
   }
);
