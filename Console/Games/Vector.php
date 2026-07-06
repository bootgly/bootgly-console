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


use function sqrt;


/**
 * Mutable 2D vector for game hot paths — positions, velocities, offsets.
 *
 * Operations mutate in place and chain, so per-tick integration allocates
 * nothing: `$Position->add($Velocity, $delta)`. Use native `clone` when an
 * independent copy is needed.
 */
class Vector
{
   // * Data
   public float $x;
   public float $y;

   // * Metadata
   /** Euclidean length (computed) */
   public float $length {
      get => sqrt($this->x * $this->x + $this->y * $this->y);
   }


   public function __construct (float $x = 0.0, float $y = 0.0)
   {
      // * Data
      $this->x = $x;
      $this->y = $y;
   }

   /**
    * Add a vector, optionally scaled — `add($Velocity, $delta)` is the
    * allocation-free Euler integration step; a negative factor subtracts.
    *
    * @param Vector $Vector The vector to add.
    * @param float $factor Scale applied to the added vector.
    *
    * @return self
    */
   public function add (Vector $Vector, float $factor = 1.0): self
   {
      // @
      $this->x += $Vector->x * $factor;
      $this->y += $Vector->y * $factor;

      // :
      return $this;
   }

   /**
    * Multiply both components — speed ramps, direction flips
    * (`scale(-1.0)`) and normalization (`scale(1.0 / $Vector->length)`).
    *
    * @param float $factor The multiplier.
    *
    * @return self
    */
   public function scale (float $factor): self
   {
      // @
      $this->x *= $factor;
      $this->y *= $factor;

      // :
      return $this;
   }
}
