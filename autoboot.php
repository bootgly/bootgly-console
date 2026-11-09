<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

// ?
if (defined('CONSOLE_ROOT_BASE') === true) {
   return;
}

// !
define('CONSOLE_ROOT_BASE', __DIR__);
define('CONSOLE_ROOT_DIR', __DIR__ . DIRECTORY_SEPARATOR);
if (defined('CONSOLE_WORKING_BASE') === false) {
   define('CONSOLE_WORKING_BASE', CONSOLE_ROOT_BASE);
   define('CONSOLE_WORKING_DIR', CONSOLE_ROOT_DIR);
}

define('CONSOLE_VERSION', '1.2.0');

// ! Bootables ([0-9]) || (-[a-z]) || ([0-9]-[a-z])
// -- nothing --

// ! Classes ([A-Z])
// Console\* (platform entities) + global Console (bootable)
spl_autoload_register (function (string $class) {
   $paths = explode('\\', $class);
   $file = implode('/', $paths) . '.php';

   $included = @include(CONSOLE_WORKING_DIR . $file);

   if ($included === false && CONSOLE_ROOT_DIR !== CONSOLE_WORKING_DIR) {
      @include(CONSOLE_ROOT_DIR . $file);
   }
});

// ! Resources ([a-z])
// ...

// @ The `Console` root implements `Bootgly\API\Platform`, so Bootgly creates it
//   (the global `Console` constant) and boots it once its own autoloader exists:
//   `Bootgly::autoboot()` when this file runs first (the launchers), here when
//   Bootgly is already booted (Composer's `files` order)
if (defined('Bootgly') === true) {
   // ? A Bootgly older than 1.1 cannot mount it
   if (method_exists('Bootgly', 'mount') === false) {
      throw new LogicException('The Console platform requires Bootgly 1.1 or later.');
   }

   Bootgly::mount('Console');
}
