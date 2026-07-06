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

use projects\Snake\Snake;


return new Project(
   // # Project Metadata
   name: 'Snake',
   description: 'Classic Snake game — Console platform Games module demo',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,
   boot: function (array $arguments = [], array $options = []): void
   {
      $Snake = new Snake;
      $Snake->run();
   }
);
