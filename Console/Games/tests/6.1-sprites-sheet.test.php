<?php

namespace Console\Games;

use function assert;
use InvalidArgumentException;

use Bootgly\ACI\Tests\Suite\Test\Specification;

return new Specification(
   description: 'It should register sprites, share instances for lockstep and load sheet files',
   test: function () {
      // ! Sheet with one two-frame sprite
      $Sprites = new Sprites;
      $Sprites->add(new Sprite('alien', frames: ["A", "B"]));

      // @ get() returns the shared instance
      yield assert(
         assertion: $Sprites->get('alien') === $Sprites->get('alien'),
         description: 'get() returns the same shared instance'
      );

      // @ Lockstep: one $frame write is visible through every reference
      $First = $Sprites->get('alien');
      $Second = $Sprites->get('alien');
      $First->frame = 1;

      yield assert(
         assertion: $Second->frame === 1,
         description: 'Shared instance animates every consumer in lockstep'
      );

      // @ clone gives independent per-entity animation state
      $Clone = clone $First;
      $Clone->frame = 0;

      yield assert(
         assertion: $First->frame === 1 && $Clone->frame === 0,
         description: 'clone diverges from the shared instance'
      );

      // @ load() registers every Sprite a sheet file returns
      $Sprites->load(__DIR__ . '/sprites/test.sprites.php');

      yield assert(
         assertion: $Sprites->get('dot')->width === 1 && $Sprites->get('spin')->FPS === 4.0,
         description: 'load() registers the sprites returned by the sheet file'
      );

      // @ Unknown sprite throws
      $caught = false;
      try {
         $Sprites->get('unknown');
      }
      catch (InvalidArgumentException) {
         $caught = true;
      }

      yield assert(
         assertion: $caught === true,
         description: 'get() throws InvalidArgumentException for unknown sprites'
      );

      // @ Missing sheet file throws
      $caught = false;
      try {
         $Sprites->load(__DIR__ . '/sprites/missing.sprites.php');
      }
      catch (InvalidArgumentException) {
         $caught = true;
      }

      yield assert(
         assertion: $caught === true,
         description: 'load() throws InvalidArgumentException for missing sheet files'
      );
   }
);
