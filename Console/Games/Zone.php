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


use function max;
use function min;


/**
 * Axis-aligned bounding box (AABB) in logical pixels — hitboxes, play
 * fields and formation extents.
 *
 * Overlap (`check`) uses strict edges, so adjacent boxes touching each
 * other do not collide; point containment (`contain`) uses inclusive
 * edges, so grazing projectiles still hit.
 */
class Zone
{
   // * Config
   /** Box width (logical pixels) */
   public float $width;
   /** Box height (logical pixels) */
   public float $height;

   // * Data
   /** Left edge */
   public float $x;
   /** Top edge */
   public float $y;


   public function __construct (float $x, float $y, float $width, float $height)
   {
      // * Config
      $this->width = $width;
      $this->height = $height;

      // * Data
      $this->x = $x;
      $this->y = $y;
   }

   /**
    * Whether the zones overlap (strict edges — touching boxes do not collide).
    *
    * @param Zone $Zone The zone to test against.
    *
    * @return bool Whether the zones overlap.
    */
   public function check (Zone $Zone): bool
   {
      // :
      return $this->x < $Zone->x + $Zone->width
         && $this->x + $this->width > $Zone->x
         && $this->y < $Zone->y + $Zone->height
         && $this->y + $this->height > $Zone->y;
   }

   /**
    * Whether the point is inside the zone (inclusive edges).
    *
    * @param Vector $Vector The point to test.
    *
    * @return bool Whether the point is inside.
    */
   public function contain (Vector $Vector): bool
   {
      // :
      return $Vector->x >= $this->x
         && $Vector->x <= $this->x + $this->width
         && $Vector->y >= $this->y
         && $Vector->y <= $this->y + $this->height;
   }

   /**
    * Clamp a point into the zone — mutates and returns the same Vector.
    *
    * @param Vector $Vector The point to clamp.
    *
    * @return Vector The same, now clamped, Vector.
    */
   public function clamp (Vector $Vector): Vector
   {
      // @
      $Vector->x = max($this->x, min($this->x + $this->width, $Vector->x));
      $Vector->y = max($this->y, min($this->y + $this->height, $Vector->y));

      // :
      return $Vector;
   }
}
