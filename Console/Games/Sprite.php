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


use function count;
use function explode;
use function max;
use function mb_str_split;


/**
 * Unicode bitmap sprite — WYSIWYG multiline frames, 1 character = 1 logical
 * pixel (the Canvas aspect doubles pixels on screen automatically).
 *
 * Animation runs on two paths: assign `$frame` directly for step-driven
 * flips (a formation marching in lockstep), or set `$FPS` and call `tick()`
 * for wall-time animation (an explosion flicker). Native `clone` yields an
 * independent copy — per-entity frame and clock.
 */
class Sprite
{
   // * Config
   /** Sprite name (the Sprites sheet key) */
   public string $name;
   /** ANSI style prefix applied to every plotted pixel */
   public string $style;
   /** Frames per second for tick() — 0.0 = step-driven only */
   public float $FPS;
   /** Transparent character — pixels holding it are not plotted */
   public string $alpha;

   // * Data
   /** Current frame index (public — step-driven animations assign it) */
   public int $frame = 0;
   /** @var array<int,array<int,array<int,string>>> Frames as rows of single-character pixels */
   protected array $frames = [];

   // * Metadata
   /** Logical pixel width (widest frame row) */
   public private(set) int $width = 0;
   /** Logical pixel height (tallest frame) */
   public private(set) int $height = 0;
   /** Time-based frame accumulator (seconds) */
   private float $clock = 0.0;


   /**
    * @param string $name The sprite name (sheet key).
    * @param array<int,string> $frames Multiline strings — 1 character = 1 logical pixel.
    * @param string $style ANSI style prefix applied to every pixel.
    * @param float $FPS Frames per second for tick() — 0.0 = step-driven only.
    * @param string $alpha Transparent character.
    */
   public function __construct (
      string $name, array $frames, string $style = '', float $FPS = 0.0, string $alpha = ' '
   )
   {
      // * Config
      $this->name = $name;
      $this->style = $style;
      $this->FPS = $FPS;
      $this->alpha = $alpha;

      // ! Parse the WYSIWYG frames into rows of pixels
      foreach ($frames as $frame) {
         $rows = [];
         foreach (explode("\n", $frame) as $row) {
            $pixels = mb_str_split($row);

            $rows[] = $pixels;

            // * Metadata
            $this->width = max($this->width, count($pixels));
         }

         // * Data
         $this->frames[] = $rows;

         // * Metadata
         $this->height = max($this->height, count($rows));
      }
   }

   /**
    * Advance the animation clock (no-op when FPS <= 0 or single-frame).
    *
    * @param float $delta Seconds since the last tick.
    *
    * @return self
    */
   public function tick (float $delta): self
   {
      // ? Step-driven or static sprite
      $frames = count($this->frames);
      if ($this->FPS <= 0.0 || $frames < 2) {
         return $this;
      }

      // @ Advance whole frames, carrying the remainder
      $this->clock += $delta;

      $step = 1.0 / $this->FPS;
      while ($this->clock >= $step) {
         $this->clock -= $step;
         $this->frame = ($this->frame + 1) % $frames;
      }

      // :
      return $this;
   }

   /**
    * Plot the current frame at logical (x, y) — off-canvas pixels clip
    * silently and transparent (`alpha`) pixels are skipped.
    *
    * @param Canvas $Canvas The target canvas.
    * @param int $x The left logical pixel column.
    * @param int $y The top logical pixel row.
    *
    * @return self
    */
   public function stamp (Canvas $Canvas, int $x, int $y): self
   {
      // ? Empty sprite
      $frames = count($this->frames);
      if ($frames === 0) {
         return $this;
      }

      // @@ Plot the current frame, pixel by pixel
      foreach ($this->frames[$this->frame % $frames] as $dy => $row) {
         foreach ($row as $dx => $pixel) {
            // ? Transparent pixel
            if ($pixel === $this->alpha) {
               continue;
            }

            $Canvas->plot($x + $dx, $y + $dy, $pixel, $this->style);
         }
      }

      // :
      return $this;
   }
}
