<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */


use Bootgly\API\Projects\Project;

use projects\Invaders\Invaders;


return new Project(
   // # Project Metadata
   name: 'Invaders',
   description: 'Invaders — Console platform Sprites + 2D math demo',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,
   boot: function (array $arguments = [], array $options = []): void
   {
      $Invaders = new Invaders;
      $Invaders->run();
   }
);
