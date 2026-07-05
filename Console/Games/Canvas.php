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


use function count;
use function intdiv;
use function max;
use function mb_chr;
use function mb_str_split;
use function mb_strlen;

use Bootgly\CLI\Terminal\Output;

use Console\Games\Canvas\Modes;


/**
 * Cell framebuffer with double buffering and diff rendering.
 *
 * Games paint logical pixels into the back buffer (`plot()` / `draw()`);
 * `flush()` composes them into terminal cells for the active packing mode
 * (Block / Half / Braille), diffs against the front buffer and writes only
 * the dirty cell runs — an unchanged frame costs zero writes.
 */
class Canvas
{
   // ! ANSI style reset
   private const string RESET = "\e[0m";


   // * Config
   public Modes $Mode;
   /** Terminal cells per logical pixel (Block mode only — 2 ≈ square pixels) */
   public int $aspect = 1;
   /** Terminal anchor row (1-based) */
   public int $row = 1;
   /** Terminal anchor column (1-based) */
   public int $column = 1;

   // * Data
   public Output $Output;

   // * Metadata
   /** Logical pixel columns */
   public private(set) int $columns;
   /** Logical pixel rows */
   public private(set) int $rows;
   /** @var array<int,array<int,array{0: string, 1: string}>> Back buffer — pixels[y][x] = [char, style] */
   private array $pixels = [];
   /** @var array<int,array<int,string>> Front buffer — composed terminal cells */
   private array $front = [];


   public function __construct (
      Output &$Output, int $columns, int $rows, Modes $Mode = Modes::Block, int $aspect = 1
   )
   {
      // * Config
      $this->Mode = $Mode;
      $this->aspect = max(1, $aspect);

      // * Data
      $this->Output = $Output;

      // * Metadata
      $this->columns = $columns;
      $this->rows = $rows;
   }

   /**
    * Wipe the back buffer (start a new frame).
    *
    * @return self Returns the current instance for method chaining.
    */
   public function clear (): self
   {
      $this->pixels = [];

      // :
      return $this;
   }

   /**
    * Wipe both buffers — the next flush() redraws every cell.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function reset (): self
   {
      $this->pixels = [];
      $this->front = [];

      // :
      return $this;
   }

   /**
    * Paint one pixel into the back buffer.
    *
    * @param int $x The pixel column (0-based).
    * @param int $y The pixel row (0-based).
    * @param string $cell The pixel character (Block mode only — Half/Braille pack dots).
    * @param string $style ANSI style prefix (e.g. "\e[32m") applied to the composed cell.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function plot (int $x, int $y, string $cell = '█', string $style = ''): self
   {
      // ? Out of bounds — ignored
      if ($x < 0 || $x >= $this->columns || $y < 0 || $y >= $this->rows) {
         return $this;
      }

      // ? Square pixels (Block mode): 1 logical pixel = $aspect terminal cells
      if ($this->Mode === Modes::Block && $this->aspect > 1) {
         $parts = mb_str_split($cell);
         $single = count($parts) === 1;

         for ($offset = 0; $offset < $this->aspect; $offset++) {
            $char = $single === true ? $cell : ($parts[$offset] ?? ' ');
            $this->pixels[$y][$x * $this->aspect + $offset] = [$char, $style];
         }

         return $this;
      }

      // @
      $this->pixels[$y][$x] = [$cell, $style];

      // :
      return $this;
   }

   /**
    * Paint a horizontal run of characters (one pixel per character).
    *
    * @param int $x The starting pixel column (0-based).
    * @param int $y The pixel row (0-based).
    * @param string $text The characters to paint.
    * @param string $style ANSI style prefix applied to each cell.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function draw (int $x, int $y, string $text, string $style = ''): self
   {
      // ? Square pixels (Block mode): text runs one character per terminal cell
      if ($this->Mode === Modes::Block && $this->aspect > 1) {
         return $this->write($x * $this->aspect, $y, $text, $style);
      }

      // @
      foreach (mb_str_split($text) as $offset => $char) {
         $this->plot($x + $offset, $y, $char, $style);
      }

      // :
      return $this;
   }

   /**
    * Paint a horizontal text run centered on a row (one character per
    * terminal cell, aspect-independent).
    *
    * @param int $y The pixel row (0-based).
    * @param string $text The characters to paint.
    * @param string $style ANSI style prefix applied to each cell.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function center (int $y, string $text, string $style = ''): self
   {
      $columns = $this->Mode === Modes::Block ? $this->columns * $this->aspect : $this->columns;
      $column = max(0, intdiv($columns - mb_strlen($text), 2));

      // :
      return $this->write($column, $y, $text, $style);
   }

   /**
    * Paint characters into raw terminal cells of a row (internal — bypasses
    * the pixel aspect).
    */
   private function write (int $column, int $y, string $text, string $style = ''): self
   {
      // ? Out of bounds row — ignored
      if ($y < 0 || $y >= $this->rows) {
         return $this;
      }

      // !
      $limit = $this->Mode === Modes::Block ? $this->columns * $this->aspect : $this->columns;

      // @
      foreach (mb_str_split($text) as $offset => $char) {
         $cell = $column + $offset;

         if ($cell < 0 || $cell >= $limit) {
            continue;
         }

         $this->pixels[$y][$cell] = [$char, $style];
      }

      // :
      return $this;
   }

   /**
    * Resize the logical pixel grid (SIGWINCH hook target) — forces a full redraw.
    *
    * @param int $columns The new pixel columns.
    * @param int $rows The new pixel rows.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function resize (int $columns, int $rows): self
   {
      // * Metadata
      $this->columns = $columns;
      $this->rows = $rows;

      // @
      $this->reset();

      // :
      return $this;
   }

   /**
    * Compose the back buffer into terminal cells, diff against the front
    * buffer and write only the dirty cell runs.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function flush (): self
   {
      // ! Composed cell grid for the active mode
      [$columns, $rows] = match ($this->Mode) {
         Modes::Block => [$this->columns * $this->aspect, $this->rows],
         Modes::Half => [$this->columns, intdiv($this->rows + 1, 2)],
         Modes::Braille => [intdiv($this->columns + 1, 2), intdiv($this->rows + 3, 4)]
      };

      // !
      $writes = '';

      // @@ Diff row by row, emitting contiguous dirty runs
      for ($row = 0; $row < $rows; $row++) {
         $run = '';
         $start = -1;

         for ($col = 0; $col < $columns; $col++) {
            $cell = $this->compose($col, $row);

            // ? Clean cell — close the open run ('' = unknown screen content:
            // never matches, so a fresh/reset front repaints every cell,
            // blanks included — this is what actually clears the region)
            if (($this->front[$row][$col] ?? '') === $cell) {
               if ($start >= 0) {
                  $writes .= $this->move($start, $row) . $run;
                  $run = '';
                  $start = -1;
               }

               continue;
            }

            // @ Dirty cell — open/extend the run
            if ($start < 0) {
               $start = $col;
            }
            $run .= $cell;
            $this->front[$row][$col] = $cell;
         }

         if ($start >= 0) {
            $writes .= $this->move($start, $row) . $run;
         }
      }

      // ? Unchanged frame — zero writes
      if ($writes !== '') {
         $this->Output->write($writes);
      }

      // :
      return $this;
   }

   /**
    * Compose one terminal cell from the back-buffer pixels (mode packing).
    */
   private function compose (int $col, int $row): string
   {
      switch ($this->Mode) {
         case Modes::Block:
            $pixel = $this->pixels[$row][$col] ?? null;
            // ?
            if ($pixel === null) {
               return ' ';
            }

            // :
            return $pixel[1] === ''
               ? $pixel[0]
               : $pixel[1] . $pixel[0] . self::RESET;

         case Modes::Half:
            $top = $this->pixels[$row * 2][$col] ?? null;
            $bottom = $this->pixels[$row * 2 + 1][$col] ?? null;

            $char = match (true) {
               $top !== null && $bottom !== null => '█',
               $top !== null => '▀',
               $bottom !== null => '▄',
               default => ' '
            };
            // ?
            if ($char === ' ') {
               return ' ';
            }

            $style = $top[1] ?? $bottom[1] ?? '';

            // :
            return $style === '' ? $char : $style . $char . self::RESET;

         case Modes::Braille:
            // ! Braille dot bit map (column, row) → bit
            $bits = 0;
            $style = '';
            $map = [
               [0x01, 0x08],
               [0x02, 0x10],
               [0x04, 0x20],
               [0x40, 0x80]
            ];

            // @@
            for ($dy = 0; $dy < 4; $dy++) {
               for ($dx = 0; $dx < 2; $dx++) {
                  $pixel = $this->pixels[$row * 4 + $dy][$col * 2 + $dx] ?? null;

                  if ($pixel !== null) {
                     $bits |= $map[$dy][$dx];

                     if ($style === '') {
                        $style = $pixel[1];
                     }
                  }
               }
            }

            // ?
            if ($bits === 0) {
               return ' ';
            }

            $char = (string) mb_chr(0x2800 + $bits);

            // :
            return $style === '' ? $char : $style . $char . self::RESET;
      }
   }

   /**
    * Cursor move to a composed cell (anchored terminal coordinates).
    */
   private function move (int $col, int $row): string
   {
      $line = $this->row + $row;
      $column = $this->column + $col;

      // :
      return "\e[{$line};{$column}H";
   }
}
