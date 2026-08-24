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

use Demo\Snake\Snake;


return new Project(
   // # Project Metadata
   name: 'Snake',
   description: 'Classic Snake game — Console platform Game module demo',
   version: '1.0.0',
   author: 'Bootgly',
   exportable: true,
   boot: function (array $arguments = [], array $options = []): void
   {
      $Snake = new Snake;
      $Snake->run();
   }
);
