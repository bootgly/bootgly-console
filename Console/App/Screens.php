<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\App;


use function array_pop;
use function count;

use Console\App\Screens\Screen;


/**
 * Screen navigation stack over the Router.
 *
 * `switch()` replaces the current screen, `push()` overlays a new one
 * (modal-style navigation) and `pop()` returns to the previous screen.
 * The topmost screen is the one rendered and receiving input.
 */
class Screens
{
   // * Config
   // ...

   // * Data
   public Router $Router;
   /** @var array<int,Screen> */
   public private(set) array $Stack = [];

   // * Metadata
   public null|Screen $Current {
      get => $this->Stack[count($this->Stack) - 1] ?? null;
   }


   public function __construct ()
   {
      // * Data
      $this->Router = new Router;
   }

   /**
    * Load a screens manifest (delegates to the Router).
    *
    * @param string $path The screens directory.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function load (string $path): self
   {
      $this->Router->load($path);

      // :
      return $this;
   }

   /**
    * Switch to a screen, replacing the current one.
    *
    * @param string $screen The screen name.
    * @param array<string,mixed> $state Navigation state passed to the Screen.
    *
    * @return Screen The activated Screen.
    */
   public function switch (string $screen, array $state = []): Screen
   {
      // !
      $Screen = new Screen($screen, $this->Router->resolve($screen), $state);

      // @ Replace the top of the stack (or start it)
      if ($this->Stack !== []) {
         array_pop($this->Stack);
      }
      $this->Stack[] = $Screen;

      // :
      return $Screen;
   }

   /**
    * Push a screen on top of the current one (modal-style navigation).
    *
    * @param string $screen The screen name.
    * @param array<string,mixed> $state Navigation state passed to the Screen.
    *
    * @return Screen The activated Screen.
    */
   public function push (string $screen, array $state = []): Screen
   {
      // !
      $Screen = new Screen($screen, $this->Router->resolve($screen), $state);

      // @
      $this->Stack[] = $Screen;

      // :
      return $Screen;
   }

   /**
    * Pop the topmost screen, returning to the previous one.
    *
    * @return null|Screen The popped Screen — null when the stack is empty.
    */
   public function pop (): null|Screen
   {
      // :
      return array_pop($this->Stack);
   }
}
