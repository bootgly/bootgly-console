<?php

namespace Console\Games;

use function assert;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Console\Games\Scenes\Scene;
use InvalidArgumentException;

return new Specification(
   description: 'It should register scenes and switch between them running the enter hooks',
   test: function () {
      // ! Scenes
      $Scenes = new Scenes;

      $entered = [];
      $Menu = new Scene('Menu', enter: function (Scene $Scene) use (&$entered): void {
         $entered[] = $Scene->name;
      });
      $Play = new Scene('Play', enter: function (Scene $Scene) use (&$entered): void {
         $entered[] = $Scene->name;
      });

      $Scenes->add($Menu)->add($Play);

      // @ No current scene before the first switch
      yield assert(
         assertion: $Scenes->Current === null,
         description: 'Current is null before the first switch'
      );

      // @ switch() activates and runs the enter hook
      $Scenes->switch('Menu');
      yield assert(
         assertion: $Scenes->Current === $Menu && $entered === ['Menu'],
         description: 'switch() activates the scene and runs its enter hook'
      );

      $Scenes->switch('Play');
      yield assert(
         assertion: $Scenes->Current === $Play && $entered === ['Menu', 'Play'],
         description: 'switch() transitions between scenes'
      );

      // @ Unknown scene throws
      $caught = false;
      try {
         $Scenes->switch('Nope');
      }
      catch (InvalidArgumentException) {
         $caught = true;
      }
      yield assert(
         assertion: $caught === true,
         description: 'switch() throws on an unknown scene'
      );
   }
);
