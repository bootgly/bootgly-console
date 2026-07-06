<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Console;


use const BOOTGLY_TTY;
use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;
use function explode;
use function function_exists;
use function implode;
use function max;
use function mb_strlen;
use function mb_substr;
use function pcntl_signal_dispatch;
use function preg_replace;
use function preg_split;
use function usleep;

use const Bootgly\CLI;
use Bootgly\CLI\Terminal;
use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Input\Keystrokes;
use Bootgly\CLI\Terminal\Output;
use Bootgly\CLI\Terminal\Screen;
use Console\App\Keymaps;
use Console\App\Palette;
use Console\App\Screens;
use Console\App\Statusbar;
use Console\App\Toasts;


/**
 * TUI application shell (single content pane + status bar + overlays).
 *
 * The App owns the terminal lifecycle — alternate screen, raw input, resize
 * tracking, restore-on-exit — and runs a single-process, non-blocking loop:
 * drain input → dispatch keymaps → render the current Screen. Screens are
 * view Closures navigated through `$Screens` (see `Console\App\Screens`);
 * chrome widgets (`$Statusbar`, `$Toasts`, `$Palette`, help overlay) compose
 * into every frame.
 */
class App
{
   // ! ANSI escape sequence matcher (escape-aware width fitting)
   private const string ANSI = '/\x1b\[[0-9;?]*[ -\/]*[@-~]/';


   // * Config
   /** Render throttle (frames per second) */
   public int $FPS = 30;

   // * Data
   public Keymaps $Keymaps;
   public Palette $Palette;
   public Screens $Screens;
   public Statusbar $Statusbar;
   public Toasts $Toasts;

   // * Metadata
   public private(set) bool $running = false;
   /** Help overlay visible? */
   public private(set) bool $help = false;
   /** Terminal restored? (idempotent teardown) */
   private bool $restored = true;
   public Input $Input;
   public Output $Output;
   public Screen $Screen;


   public function __construct (null|Input $Input = null, null|Output $Output = null)
   {
      // * Data
      $this->Keymaps = new Keymaps;
      $this->Screens = new Screens;
      $this->Statusbar = new Statusbar;
      $this->Toasts = new Toasts;
      $this->Palette = new Palette($this->Keymaps);

      // * Metadata
      $this->Input = $Input ?? CLI->Terminal->Input;
      $this->Output = $Output ?? CLI->Terminal->Output;
      $this->Screen = new Screen($this->Output);
   }

   /**
    * Boot the terminal for the full-screen TUI: alternate screen buffer, raw
    * input, resize tracking, restore-on-exit and the default key bindings.
    *
    * @return self Returns the current instance for method chaining.
    */
   public function boot (): self
   {
      // @ Default bindings
      $this->Keymaps->bind(Keystrokes::CTRL_P, 'Command palette', function (): void {
         $this->Palette->toggle();
      });
      $this->Keymaps->bind('?', 'Help', function (): void {
         $this->help = $this->help === false;
      });
      $this->Keymaps->bind('q', 'Quit', function (): void {
         $this->quit();
      });

      // ? Interactive TTY only — non-interactive runs render a single frame
      if (BOOTGLY_TTY === false) {
         return $this;
      }

      // @ Enter the full-screen TUI
      // Restore-on-exit (any path, including Ctrl+C / SIGTERM) is covered by the
      // core net: Screen->open() self-registers the buffer restore and the raw
      // configure() below arms Input's stty/cursor/mouse restore + INT/TERM handlers.
      $this->restored = false;
      $this->Screen->open();
      $this->Output->Cursor->hide();
      $this->Input->configure(blocking: false, canonical: false, echo: false);

      // @ Track terminal resizes
      $this->Screen->watch(static function (int $columns, int $lines): void {
         Terminal::$columns = $columns;
         Terminal::$lines = $lines;
         Terminal::$width = $columns;
         Terminal::$height = $lines;
      });

      // :
      return $this;
   }

   /**
    * Run the main loop: drain input → dispatch keymaps → render → throttle.
    * Returns when `quit()` is called or the screen stack empties.
    *
    * @param null|string $screen The initial screen to switch to.
    */
   public function run (null|string $screen = null): void
   {
      // ! Initial screen
      if ($screen !== null) {
         $this->Screens->switch($screen);
      }

      // ? Non-interactive (pipes, CI, embedded runtimes): render one frame
      if (BOOTGLY_TTY === false) {
         $this->render();

         return;
      }

      // * Metadata
      $this->running = true;

      // @@ Main loop
      while ($this->running === true && $this->Screens->Current !== null) {
         // @ Dispatch pending signals (resize, interrupt)
         if (function_exists('pcntl_signal_dispatch') === true) {
            pcntl_signal_dispatch();
         }

         // @ Handle one keystroke (non-blocking; escape sequences arrive whole)
         $key = $this->Input->read(8);
         if ($key !== false && $key !== '') {
            $this->control($key);
         }

         // @ Redraw + throttle
         $this->render();
         usleep((int) (1000000 / $this->FPS));
      }

      $this->restore();
   }

   /**
    * Stop the main loop.
    */
   public function quit (): void
   {
      // * Metadata
      $this->running = false;
   }

   /**
    * Render one full frame: content pane (current Screen view or an active
    * overlay), toast rows overlaid on top, status bar on the last row.
    */
   public function render (): void
   {
      // !
      $height = Terminal::$height;
      $pane = max(1, $height - 1); // content rows (status bar takes the last row)

      // ! Content — an active overlay replaces the Screen view
      $Screen = $this->Screens->Current;
      $content = '';
      if ($this->help === true) {
         $content = $this->assist();
      }
      else if ($this->Palette->active === true) {
         $content = (string) $this->Palette->render();
      }
      else if ($Screen !== null) {
         $content = ($Screen->View)($this, $Screen);
      }

      $lines = explode("\n", $content);

      // @ Overlay toasts on the top rows
      $this->Toasts->expire();
      $toasts = (string) $this->Toasts->render();
      if ($toasts !== '') {
         foreach (explode("\n", $toasts) as $index => $toast) {
            $lines[$index] = $toast;
         }
      }

      // @ Build the frame (cursor home, per-line clear-to-EOL avoids flicker)
      $frame = "\e[H";
      $rows = 0;
      foreach ($lines as $line) {
         if ($rows >= $pane) {
            break;
         }

         $frame .= $this->fit($line) . "\e[K\n";
         $rows++;
      }
      for (; $rows < $pane; $rows++) {
         $frame .= "\e[K\n";
      }

      $frame .= $this->fit((string) $this->Statusbar->render()) . "\e[K";

      $this->Output->write($frame);
   }

   /**
    * Dispatch a keystroke: active overlays first, then the current Screen
    * keymaps, then the global keymaps.
    *
    * @param string $key Raw bytes read from the terminal.
    *
    * @return bool Whether the key was consumed.
    */
   protected function control (string $key): bool
   {
      // ? Command palette captures all input while active
      if ($this->Palette->active === true) {
         return $this->Palette->control($key);
      }
      // ? Help overlay: any key dismisses it
      if ($this->help === true) {
         $this->help = false;

         return true;
      }

      // @ Screen keymaps first, then the global keymaps
      $Screen = $this->Screens->Current;
      if ($Screen !== null && $Screen->Keymaps->handle($key) === true) {
         return true;
      }

      // :
      return $this->Keymaps->handle($key);
   }

   /**
    * Build the help overlay content (auto-generated from the keymaps).
    */
   protected function assist (): string
   {
      $lines = [' Keymaps', ''];

      // @ Current Screen bindings first, then the global ones
      $Screen = $this->Screens->Current;
      $groups = $Screen !== null
         ? [$Screen->name => $Screen->Keymaps, 'Global' => $this->Keymaps]
         : ['Global' => $this->Keymaps];

      foreach ($groups as $group => $Keymaps) {
         $bindings = $Keymaps->list();
         if ($bindings === []) {
            continue;
         }

         $lines[] = " $group:";
         foreach ($bindings as $binding) {
            $lines[] = "   {$binding['keys']}  —  {$binding['label']}";
         }
         $lines[] = '';
      }

      $lines[] = ' (press any key to dismiss)';

      // :
      return implode("\n", $lines);
   }

   /**
    * Fit a styled line into the terminal width (escape-aware truncation) —
    * a wrapped line would add a row and desync the `\e[H`-anchored redraws.
    */
   protected function fit (string $line): string
   {
      // ?
      $width = Terminal::$width;
      $plain = (string) preg_replace(self::ANSI, '', $line);
      if (mb_strlen($plain) <= $width) {
         return $line;
      }

      // !
      $tokens = preg_split(
         '/(\x1b\[[0-9;?]*[ -\/]*[@-~])/',
         $line,
         -1,
         PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
      );
      $output = '';
      $visible = 0;

      // @@
      foreach ($tokens === false ? [$line] : $tokens as $token) {
         // ? Escape sequences pass through whole — they occupy no columns
         if ($token[0] === "\e") {
            $output .= $token;

            continue;
         }

         $remaining = $width - $visible;
         if ($remaining <= 0) {
            continue;
         }

         $chunk = mb_substr($token, 0, $remaining);
         $output .= $chunk;
         $visible += mb_strlen($chunk);
      }

      // :
      return $output;
   }

   /**
    * Restore the terminal (idempotent): line input, cursor, main screen buffer.
    */
   protected function restore (): void
   {
      // ?
      if ($this->restored === true) {
         return;
      }

      // * Metadata
      $this->restored = true;

      // @
      $this->Screen->watch(null);
      $this->Input->configure(blocking: true, canonical: true, echo: true);
      $this->Output->Cursor->show();
      $this->Screen->close();
   }
}
