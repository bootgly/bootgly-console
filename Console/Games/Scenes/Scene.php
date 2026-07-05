<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console\Games\Scenes;


use Closure;


/**
 * One game scene: named enter / update / render hooks plus scene state.
 */
class Scene
{
   // * Config
   public string $name;

   // * Data
   public null|Closure $enter;
   public null|Closure $update;
   public null|Closure $render;
   /** @var array<string,mixed> */
   public array $state = [];

   // * Metadata
   // ...


   /**
    * @param string $name The scene name.
    * @param null|Closure $enter Called when the scene activates — function (Scene $Scene): void.
    * @param null|Closure $update Simulation hook — function (float $delta, Scene $Scene): void.
    * @param null|Closure $render Render hook — function (Scene $Scene): void.
    */
   public function __construct (
      string $name,
      null|Closure $enter = null,
      null|Closure $update = null,
      null|Closure $render = null
   )
   {
      // * Config
      $this->name = $name;

      // * Data
      $this->enter = $enter;
      $this->update = $update;
      $this->render = $render;
   }
}
