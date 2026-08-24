<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */


use Bootgly\API\Projects\Project;

use Demo\Pong\Pong;


return new Project(
   // # Project Metadata
   name: 'Pong',
   description: 'Pong vs AI — Console platform Game module demo',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,
   boot: function (array $arguments = [], array $options = []): void
   {
      $Pong = new Pong;
      $Pong->run();
   }
);
