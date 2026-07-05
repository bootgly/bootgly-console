<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console;


use const BOOTGLY_TTY;
use function intdiv;
use function max;
use function min;
use function str_starts_with;
use function strlen;
use function substr;
use function usleep;

use Bootgly\CLI\Terminal;
use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Input\Keystrokes;
use Bootgly\CLI\Terminal\Output;
use Console\Games\Canvas;
use Console\Games\Keyboard;
use Console\Games\Loop;
use Console\Games\Scenes;
use Console\Games\Sprites;


/**
 * Game shell over the Terminal Client/Server interface.
 *
 * `run()` forks the roles through `Input->reading()`: the Client pumps
 * keystrokes as newline-framed tokens into the channel; the Server owns the
 * screen and runs the fixed-timestep `Loop` — tokens feed the `Keyboard`,
 * each tick calls `update()`, each frame calls `draw()` and diff-flushes the
 * `Canvas`. Embedded runtimes (WASM) run one role per process transparently.
 */
abstract class Games extends App
{
   // * Config
   // ...

   // * Data
   public Canvas $Canvas;
   public Keyboard $Keyboard;
   public Loop $Loop;
   public Scenes $Scenes;
   public Sprites $Sprites;

   // * Metadata
   // ...


   public function __construct (
      null|Input $Input = null,
      null|Output $Output = null,
      int $columns = 80,
      int $rows = 22,
      int $aspect = 1
   )
   {
      parent::__construct($Input, $Output);

      // ? Fit the design size to the real terminal — `$columns`/`$rows` act
      // as caps; the status bar keeps the last terminal row
      if (isSet(Terminal::$columns) === true) {
         $columns = min($columns, intdiv(Terminal::$columns, max(1, $aspect)));
         $rows = min($rows, Terminal::$lines - 1);
      }
      $columns = max($columns, 24);
      $rows = max($rows, 12);

      // * Data
      $this->Canvas = new Canvas($this->Output, $columns, $rows, aspect: $aspect);
      $this->Keyboard = new Keyboard;
      $this->Loop = new Loop($this->Keyboard);
      $this->Scenes = new Scenes;
      $this->Sprites = new Sprites;
   }

   /**
    * Run the game: fork the Terminal Client/Server pair and drive the loop.
    *
    * @param null|string $screen Unused by games (App signature compatibility).
    */
   public function run (null|string $screen = null): void
   {
      // ? Non-interactive (pipes, CI): simulate one tick and render one frame
      if (BOOTGLY_TTY === false) {
         $this->update(1.0 / $this->Loop->tps);
         $this->draw();
         $this->Canvas->flush();

         return;
      }

      // @ Fork the Terminal Client/Server pair (or relay a single role — WASM)
      $this->Input->reading(
         CAPI: function ($read, $write): void {
            $this->client($read, $write);
         },
         SAPI: function ($reading): void {
            $this->serve($reading);
         }
      );
   }

   /**
    * Terminal Client role: pump keystrokes into the channel as
    * newline-framed tokens (escape sequences assembled and normalized
    * to `Keystrokes` names).
    *
    * @param callable $read function (int $length): string|false — raw terminal input.
    * @param callable $write function (string $data): int|false — channel writer.
    */
   protected function client (callable $read, callable $write): void
   {
      // * Config
      $delay = 2000;      // µs between input polls (native non-blocking reads only)
      $settle = 250000;   // µs grace before ending on quit — lets the Server restore the screen

      // ! Token assembly buffer (escape sequences may split across reads)
      $buffer = '';

      // @@ Pump
      while (true) {
         $chunk = $read(64);

         // ? Terminal input closed
         if ($chunk === false) {
            break;
         }
         // ? No data (native non-blocking reads; embedded runtimes block instead)
         if ($chunk === '') {
            // @ A stale escape prefix will not complete — flush it as a lone ESC
            if ($buffer !== '') {
               $write("ESCAPE\n");
               $buffer = substr($buffer, 1);

               continue;
            }

            usleep($delay);

            continue;
         }

         $buffer .= $chunk;

         // @@ Parse complete tokens (longest escape match first: 6 → 2 bytes)
         while ($buffer !== '') {
            if ($buffer[0] === "\e") {
               // @ Escape sequence: longest Keystrokes match wins
               $matched = null;
               for ($bytes = min(strlen($buffer), 6); $bytes >= 2; $bytes--) {
                  $matched = Keystrokes::tryFrom(substr($buffer, 0, $bytes));

                  if ($matched !== null) {
                     break;
                  }
               }

               if ($matched !== null) {
                  $write("{$matched->name}\n");
                  $buffer = substr($buffer, strlen($matched->value));

                  continue;
               }
               // ? Incomplete sequence split across reads — wait for the tail
               if (strlen($buffer) < 6 && self::prefixed($buffer) === true) {
                  break;
               }

               // : Lone / unknown ESC
               $write("ESCAPE\n");
               $buffer = substr($buffer, 1);

               continue;
            }

            // @ Single-byte token (\r normalized: raw terminals deliver Enter as CR)
            $char = $buffer[0];
            $buffer = substr($buffer, 1);

            $Keystroke = $char === "\r" ? Keystrokes::ENTER : Keystrokes::tryFrom($char);
            $token = $Keystroke !== null ? $Keystroke->name : $char;

            $write("$token\n");

            // ? Quit convention: end the pump — embedded runtimes (WASM) have no
            // parent process to terminate the Client; harmless natively (the
            // Server parent kills the child anyway)
            if ($token === 'q') {
               usleep($settle);

               return;
            }
         }
      }
   }

   /**
    * Whether any Keystrokes sequence starts with the given bytes
    * (incomplete escape sequence check).
    */
   private static function prefixed (string $bytes): bool
   {
      foreach (Keystrokes::cases() as $Keystroke) {
         if (str_starts_with($Keystroke->value, $bytes) === true) {
            return true;
         }
      }

      // :
      return false;
   }

   /**
    * Terminal Server role: own the screen and run the fixed-timestep loop.
    *
    * @param callable $reading The channel generator factory (see Loop::run).
    */
   protected function serve (callable $reading): void
   {
      // @ Enter the full-screen TUI (the Client child owns raw input)
      $this->Screen->open();
      $this->Output->Cursor->hide();

      // @ Center the board in the terminal (status bar keeps the last row)
      $width = $this->Canvas->columns * $this->Canvas->aspect;
      $this->Canvas->column = max(1, intdiv(Terminal::$columns - $width, 2) + 1);
      $this->Canvas->row = max(1, intdiv(Terminal::$lines - 1 - $this->Canvas->rows, 2) + 1);

      // @ Track terminal resizes
      $this->Screen->watch(static function (int $columns, int $lines): void {
         Terminal::$columns = $columns;
         Terminal::$lines = $lines;
         Terminal::$width = $columns;
         Terminal::$height = $lines;
      });

      // @ Fixed-timestep game loop over the input channel
      // Abnormal exits are covered by the core net: Screen->open() self-registers
      // the buffer restore and Input's reading() arms the stty/cursor/mouse restore.
      $status = null;
      $this->Loop->run(
         $reading,
         update: function (float $delta): void {
            $this->update($delta);
         },
         render: function () use (&$status): void {
            $this->draw();
            $this->Canvas->flush();

            // @ Status bar on the last terminal row (rewritten only on change)
            $bar = $this->fit((string) $this->Statusbar->render());
            if ($bar !== $status) {
               $status = $bar;
               $row = Terminal::$height;
               $this->Output->write("\e[{$row};1H{$bar}\e[K");
            }
         }
      );

      // @ Leave the full-screen TUI (clean exit path)
      $this->Screen->watch(null);
      $this->Output->Cursor->show();
      $this->Screen->close();
   }

   /**
    * Simulation tick (fixed timestep).
    *
    * @param float $delta Seconds per tick (1 / tps).
    */
   abstract protected function update (float $delta): void;

   /**
    * Paint the frame into the Canvas (flushed by the shell after).
    */
   abstract protected function draw (): void;
}
