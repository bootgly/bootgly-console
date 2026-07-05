<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Invaders;


use Console\Games\Vector;


/**
 * A projectile — player shot or alien bomb (they differ only in velocity).
 */
class Bolt
{
   // * Data
   /** Position (logical pixels) */
   public Vector $Position;
   /** Velocity (pixels per second) */
   public Vector $Velocity;


   public function __construct (Vector $Position, Vector $Velocity)
   {
      // * Data
      $this->Position = $Position;
      $this->Velocity = $Velocity;
   }
}
