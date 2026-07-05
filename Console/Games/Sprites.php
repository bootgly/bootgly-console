<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\Games;


use function is_array;
use function is_file;
use InvalidArgumentException;


/**
 * Named sprite sheet — the game's sprite registry.
 *
 * `get()` returns the shared instance, so entities drawn from the same
 * sprite animate in lockstep with a single `$frame` write; `clone` the
 * result when an entity needs independent animation state.
 */
class Sprites
{
   // * Data
   /** @var array<string,Sprite> */
   protected array $Sprites = [];


   /**
    * Register a sprite (keyed by its name).
    *
    * @param Sprite $Sprite The sprite to register.
    *
    * @return self
    */
   public function add (Sprite $Sprite): self
   {
      // * Data
      $this->Sprites[$Sprite->name] = $Sprite;

      // :
      return $this;
   }

   /**
    * The shared sprite instance (registry semantics — lockstep animation);
    * `clone` the result for per-entity animation state.
    *
    * @param string $name The sprite name.
    *
    * @throws InvalidArgumentException When the sprite is unknown.
    *
    * @return Sprite The shared sprite instance.
    */
   public function get (string $name): Sprite
   {
      // ? Unknown sprite
      if (isSet($this->Sprites[$name]) === false) {
         throw new InvalidArgumentException("Unknown sprite: `{$name}`.");
      }

      // :
      return $this->Sprites[$name];
   }

   /**
    * Load a `<Game>.sprites.php` sheet — a file returning a list of Sprites.
    *
    * @param string $file The sheet file path.
    *
    * @throws InvalidArgumentException When the file is missing.
    *
    * @return self
    */
   public function load (string $file): self
   {
      // ? Sheet file must exist
      if (is_file($file) === false) {
         throw new InvalidArgumentException("Sprite sheet not found: `{$file}`.");
      }

      // @ Register every Sprite the sheet returns
      $Sprites = require $file;
      if (is_array($Sprites) === true) {
         foreach ($Sprites as $Sprite) {
            if ($Sprite instanceof Sprite === true) {
               $this->add($Sprite);
            }
         }
      }

      // :
      return $this;
   }
}
