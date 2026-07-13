<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Pong;


use function random_int;


class Ball
{
   // * Config
   /** Horizontal speed (cells per second) */
   public float $speed = 24.0;
   /** Max vertical speed on serve (cells per second) */
   public float $spin = 8.0;

   // * Data
   // Position
   public float $x = 0.0;
   public float $y = 0.0;
   // Velocity (cells per second)
   public float $dx = 0.0;
   public float $dy = 0.0;

   // * Metadata
   // ...


   /**
    * Serve the ball from the given position toward one side.
    *
    * @param float $x The serve column.
    * @param float $y The serve row.
    * @param int $direction Horizontal direction: 1 (right) or -1 (left).
    */
   public function serve (float $x, float $y, int $direction): void
   {
      // * Data
      $this->x = $x;
      $this->y = $y;
      $this->dx = $this->speed * ($direction >= 0 ? 1.0 : -1.0);
      $this->dy = random_int(-100, 100) / 100.0 * $this->spin;
   }

   /**
    * Advance the ball one simulation step.
    *
    * @param float $delta Seconds elapsed.
    */
   public function move (float $delta): void
   {
      $this->x += $this->dx * $delta;
      $this->y += $this->dy * $delta;
   }
}
