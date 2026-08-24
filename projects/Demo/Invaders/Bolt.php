<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Bootgly and contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Demo\Invaders;


use Console\Game\Vector;


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
