<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
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

define('CONSOLE_VERSION', '0.1.0-alpha');

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

// @
/**
 * @var Console Console
 */
const Console = new Console;
Console->autoboot();
