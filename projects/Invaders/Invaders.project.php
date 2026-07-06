<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
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
