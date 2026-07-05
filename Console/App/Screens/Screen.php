<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\App\Screens;


use Closure;

use Console\App\Keymaps;


/**
 * One navigable screen: a named view Closure with its own Keymaps and state.
 *
 * The view receives the App and this Screen and returns the frame content:
 * `function (App $App, Screen $Screen): string`.
 */
class Screen
{
   // * Config
   public string $name;

   // * Data
   public Keymaps $Keymaps;
   public Closure $View;
   /** @var array<string,mixed> Navigation state (passed by `switch()` / `push()`) */
   public array $state;

   // * Metadata
   // ...


   /**
    * @param string $name The screen name (route).
    * @param Closure $View The view — function (App $App, Screen $Screen): string.
    * @param array<string,mixed> $state Initial navigation state.
    */
   public function __construct (string $name, Closure $View, array $state = [])
   {
      // * Config
      $this->name = $name;

      // * Data
      $this->Keymaps = new Keymaps;
      $this->View = $View;
      $this->state = $state;
   }
}
