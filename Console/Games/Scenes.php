<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\Games;


use InvalidArgumentException;

use Console\Games\Scenes\Scene;


/**
 * Scene state machine: register scenes, switch between them (running their
 * enter hooks) and expose the current one to the game update/render cycle.
 */
class Scenes
{
   // * Config
   // ...

   // * Data
   /** @var array<string,Scene> */
   protected array $Scenes = [];

   // * Metadata
   public private(set) null|Scene $Current = null;


   /**
    * Register a scene.
    *
    * @param Scene $Scene The scene.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function add (Scene $Scene): self
   {
      $this->Scenes[$Scene->name] = $Scene;

      // :
      return $this;
   }

   /**
    * Switch to a scene, running its enter hook.
    *
    * @param string $scene The scene name.
    *
    * @return Scene The activated scene.
    *
    * @throws InvalidArgumentException When the scene is unknown.
    */
   public function switch (string $scene): Scene
   {
      // ?
      if (isSet($this->Scenes[$scene]) === false) {
         throw new InvalidArgumentException("Unknown scene: `$scene`.");
      }

      // !
      $Scene = $this->Scenes[$scene];

      // * Metadata
      $this->Current = $Scene;

      // @
      if ($Scene->enter !== null) {
         ($Scene->enter)($Scene);
      }

      // :
      return $Scene;
   }
}
