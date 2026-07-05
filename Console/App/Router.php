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


use function is_array;
use function is_file;
use function is_string;
use Closure;
use InvalidArgumentException;


/**
 * Screen router: resolves screen names to view Closures.
 *
 * Mirrors the WPI Router flavor at TUI scale: a `screens.index.php` manifest
 * names the screens, each name maps to a `<Name>.php` file returning a view
 * Closure — lazily required on first resolve. Views may also be registered
 * inline with `route()`.
 */
class Router
{
   // * Config
   // ...

   // * Data
   /** @var array<string,string> Screen name → screen file */
   protected array $files = [];
   /** @var array<string,Closure> Resolved views cache */
   protected array $Views = [];

   // * Metadata
   // ...


   /**
    * Load a screens manifest: `<path>/screens.index.php` returns the screen
    * names; each name maps to `<path>/<Name>.php`.
    *
    * @param string $path The screens directory (no trailing slash required).
    *
    * @return self Returns the current instance for method chaining.
    *
    * @throws InvalidArgumentException When the manifest is missing or invalid.
    */
   public function load (string $path): self
   {
      // !
      $manifest = "$path/screens.index.php";

      // ?
      if (is_file($manifest) === false) {
         throw new InvalidArgumentException("Screens manifest not found: `$manifest`.");
      }

      $screens = require $manifest;
      // ?
      if (is_array($screens) === false || $screens === []) {
         throw new InvalidArgumentException("Screens manifest must return a non-empty array of screen names: `$manifest`.");
      }

      // @
      foreach ($screens as $screen) {
         if (is_string($screen) === false) {
            throw new InvalidArgumentException("Screens manifest entries must be screen names (strings): `$manifest`.");
         }

         $this->files[$screen] = "$path/$screen.php";
      }

      // :
      return $this;
   }

   /**
    * Register a screen view inline.
    *
    * @param string $screen The screen name.
    * @param Closure $view The view — function (App $App, Screen $Screen): string.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function route (string $screen, Closure $view): self
   {
      $this->Views[$screen] = $view;

      // :
      return $this;
   }

   /**
    * Check whether a screen name is routable.
    */
   public function check (string $screen): bool
   {
      return isSet($this->Views[$screen]) === true || isSet($this->files[$screen]) === true;
   }

   /**
    * Resolve a screen name to its view Closure (lazily requiring its file).
    *
    * @param string $screen The screen name.
    *
    * @return Closure The view — function (App $App, Screen $Screen): string.
    *
    * @throws InvalidArgumentException When the screen is unknown or its file is invalid.
    */
   public function resolve (string $screen): Closure
   {
      // ? Cached or inline view
      if (isSet($this->Views[$screen]) === true) {
         return $this->Views[$screen];
      }
      // ? Unknown screen
      if (isSet($this->files[$screen]) === false) {
         throw new InvalidArgumentException("Unknown screen: `$screen`.");
      }

      // !
      $file = $this->files[$screen];
      // ?
      if (is_file($file) === false) {
         throw new InvalidArgumentException("Screen file not found: `$file`.");
      }

      // @
      $View = require $file;
      // ?
      if ($View instanceof Closure === false) {
         throw new InvalidArgumentException("Screen file must return a view Closure: `$file`.");
      }

      $this->Views[$screen] = $View;

      // :
      return $View;
   }
}
