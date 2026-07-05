<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace projects\Pong;


use function intdiv;
use function max;
use function min;
use function round;

use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Output;
use Console\Games;
use Console\Games\Scenes\Scene;


/**
 * Pong — 1 player vs a simple AI (Console platform Games module demo).
 *
 * Scenes: Menu → Play → Over. Hold ↑/↓ to move the left paddle,
 * first to 5 points wins, `q` quits, Enter starts / restarts.
 */
class Pong extends Games
{
   // ! ANSI styles
   private const string BORDER = "\e[90m";
   private const string PLAYER = "\e[1;32m";
   private const string RIVAL = "\e[1;31m";
   private const string BALL = "\e[1;33m";


   // * Config
   /** Paddle height (cells — scaled to the board at construct) */
   public int $size = 4;
   /** Winning score */
   public int $goal = 5;
   /** Player paddle step per keystroke (cells — scaled to the board at construct) */
   public float $step = 1.0;
   /** AI paddle speed cap (cells per second — scaled to the board at construct) */
   public float $pace = 12.0;

   // * Data
   public Ball $Ball;
   /** Player paddle top row (float — moved in keystroke impulses) */
   public float $player = 8.0;
   /** AI paddle top row */
   public float $rival = 8.0;

   // * Metadata
   /** @var array{0: int, 1: int} [player, rival] */
   public private(set) array $score = [0, 0];


   public function __construct (null|Input $Input = null, null|Output $Output = null)
   {
      parent::__construct($Input, $Output, columns: 60, rows: 30, aspect: 2);

      // ! Board size (terminal-fitted by the Games shell)
      $columns = $this->Canvas->columns;
      $rows = $this->Canvas->rows;

      // * Config — scale the gameplay to the board
      $this->size = max(4, intdiv($rows, 5));
      $this->step = max(1.0, $rows / 20.0);
      $this->pace = $rows * 0.45;

      // * Data
      $this->Ball = new Ball;
      $this->Ball->speed = $columns * 0.4;
      $this->Ball->spin = $rows * 0.4;

      // @ Game pacing
      $this->Loop->tps = 24;

      // @ Scenes
      $this->Scenes->add(new Scene(
         'Menu',
         update: function (float $delta): void {
            if ($this->Keyboard->pop('ENTER') === true || $this->Keyboard->pop('SPACE') === true) {
               $this->reset();
               $this->Scenes->switch('Play');
            }
         },
         render: function (): void {
            $middle = intdiv($this->Canvas->rows, 2);

            $this->Canvas->clear();
            $this->frame();
            $this->Canvas->center($middle - 2, 'PONG', self::BALL);
            $this->Canvas->center($middle, 'Powered by the Bootgly Console platform', self::BORDER);
            $this->Canvas->center($middle + 2, '[Enter] play    [hold ↑/↓] move    [q] quit');
         }
      ));
      $this->Scenes->add(new Scene(
         'Play',
         update: function (float $delta): void {
            $this->play($delta);
         },
         render: function (): void {
            $columns = $this->Canvas->columns;
            $rows = $this->Canvas->rows;

            $this->Canvas->clear();
            $this->frame();

            // @ Center line
            $net = intdiv($columns, 2);
            for ($y = 1; $y < $rows - 1; $y += 2) {
               $this->Canvas->plot($net, $y, '┊ ', self::BORDER);
            }

            // @ Paddles
            $player = (int) round($this->player);
            $rival = (int) round($this->rival);
            for ($cell = 0; $cell < $this->size; $cell++) {
               $this->Canvas->plot(1, $player + $cell, '█', self::PLAYER);
               $this->Canvas->plot($columns - 2, $rival + $cell, '█', self::RIVAL);
            }

            // @ Ball
            $this->Canvas->plot((int) round($this->Ball->x), (int) round($this->Ball->y), '● ', self::BALL);
         }
      ));
      $this->Scenes->add(new Scene(
         'Over',
         update: function (float $delta): void {
            if ($this->Keyboard->pop('ENTER') === true || $this->Keyboard->pop('SPACE') === true) {
               $this->reset();
               $this->Scenes->switch('Play');
            }
         },
         render: function (): void {
            $middle = intdiv($this->Canvas->rows, 2);
            $winner = $this->score[0] > $this->score[1] ? ' YOU WIN! ' : ' AI WINS! ';
            $color = $this->score[0] > $this->score[1] ? self::PLAYER : self::RIVAL;

            $this->Canvas->center($middle - 1, $winner, $color);
            $this->Canvas->center($middle + 1, '[Enter] play again    [q] quit');
         }
      ));

      $this->Scenes->switch('Menu');
      $this->reset();
   }

   /**
    * Reset the match: paddles, score and serve.
    */
   public function reset (): void
   {
      // * Data
      $this->player = ($this->Canvas->rows - $this->size) / 2.0;
      $this->rival = $this->player;

      // * Metadata
      $this->score = [0, 0];

      // @
      $this->pitch(direction: 1);
      $this->Keyboard->reset();
      $this->Canvas->reset();
   }

   protected function update (float $delta): void
   {
      // ? Quit from any scene
      if ($this->Keyboard->pop('q') === true) {
         $this->Loop->stop();

         return;
      }

      // @ Delegate to the current scene
      $Scene = $this->Scenes->Current;
      if ($Scene !== null && $Scene->update !== null) {
         ($Scene->update)($delta, $Scene);
      }
   }

   protected function draw (): void
   {
      // @ Delegate to the current scene
      $Scene = $this->Scenes->Current;
      if ($Scene !== null && $Scene->render !== null) {
         ($Scene->render)($Scene);
      }

      // @ Status bar
      $this->Statusbar->left = ['Pong', "You {$this->score[0]} × {$this->score[1]} AI"];
      $this->Statusbar->right = ['[q] quit'];
   }

   /**
    * Serve the ball from the board center toward one side.
    */
   private function pitch (int $direction): void
   {
      $this->Ball->serve(
         (float) intdiv($this->Canvas->columns, 2),
         (float) intdiv($this->Canvas->rows, 2),
         direction: $direction
      );
   }

   /**
    * Advance one Play tick: paddles, ball, collisions and scoring.
    */
   private function play (float $delta): void
   {
      // ! Board bounds (inside the border)
      $columns = $this->Canvas->columns;
      $rows = $this->Canvas->rows;
      $top = 1.0;
      $bottom = (float) ($rows - 1 - $this->size);

      // @ Player paddle: one impulse per queued keystroke — a tap is one precise
      // step with zero delay; holding streams the terminal auto-repeats
      // (capped per tick so input bursts cannot teleport the paddle)
      $moves = 0;
      while ($moves < 3 && $this->Keyboard->pop('UP') === true) {
         $this->player = max($top, $this->player - $this->step);
         $moves++;
      }
      while ($moves < 3 && $this->Keyboard->pop('DOWN') === true) {
         $this->player = min($bottom, $this->player + $this->step);
         $moves++;
      }

      // @ AI paddle: chase only while the ball approaches (capped speed);
      // drift back to the center at half pace otherwise — beatable by design
      $middle = ($rows - $this->size) / 2.0 + $this->size / 2.0;
      $target = $this->Ball->dx > 0 ? $this->Ball->y : $middle;
      $limit = ($this->Ball->dx > 0 ? $this->pace : $this->pace / 2.0) * $delta;

      $center = $this->rival + $this->size / 2.0;
      $chase = $target - $center;
      $step = min($limit, $chase >= 0 ? $chase : - $chase);
      $this->rival = max($top, min($bottom, $this->rival + ($chase >= 0 ? $step : - $step)));

      // @ Ball
      $Ball = $this->Ball;
      $Ball->move($delta);

      // @ Wall bounce (top/bottom, inside the border)
      if ($Ball->y <= 1.0) {
         $Ball->y = 1.0;
         $Ball->dy = - $Ball->dy;
      }
      else if ($Ball->y >= (float) ($rows - 2)) {
         $Ball->y = (float) ($rows - 2);
         $Ball->dy = - $Ball->dy;
      }

      // @ Paddle hits — reverse dx, deflect dy by the hit offset
      if ($Ball->dx < 0 && $Ball->x <= 2.0 && $Ball->x >= 1.0) {
         $this->deflect($this->player, 2.0);
      }
      else if ($Ball->dx > 0 && $Ball->x >= (float) ($columns - 3) && $Ball->x <= (float) ($columns - 2)) {
         $this->deflect($this->rival, (float) ($columns - 3));
      }

      // @ Scoring (ball escaped a side)
      if ($Ball->x < 1.0) {
         $this->score[1]++;
         $this->pitch(direction: -1);
      }
      else if ($Ball->x > (float) ($columns - 2)) {
         $this->score[0]++;
         $this->pitch(direction: 1);
      }

      // ? Match over
      if ($this->score[0] >= $this->goal || $this->score[1] >= $this->goal) {
         $this->Scenes->switch('Over');
      }
   }

   /**
    * Paint the board border.
    */
   private function frame (): void
   {
      $columns = $this->Canvas->columns;
      $rows = $this->Canvas->rows;

      // @@
      for ($x = 0; $x < $columns; $x++) {
         $this->Canvas->plot($x, 0, '· ', self::BORDER);
         $this->Canvas->plot($x, $rows - 1, '· ', self::BORDER);
      }
      for ($y = 1; $y < $rows - 1; $y++) {
         $this->Canvas->plot(0, $y, '· ', self::BORDER);
         $this->Canvas->plot($columns - 1, $y, ' ·', self::BORDER);
      }
   }

   /**
    * Deflect the ball off a paddle when it overlaps it — otherwise let it pass.
    *
    * @param float $paddle The paddle top row.
    * @param float $x The rebound column.
    */
   private function deflect (float $paddle, float $x): void
   {
      $Ball = $this->Ball;

      // ? Ball misses the paddle
      if ($Ball->y < $paddle - 0.5 || $Ball->y > $paddle + $this->size + 0.5) {
         return;
      }

      // @ Reverse and deflect by the hit offset (center = straight, edges = steep)
      $offset = ($Ball->y - ($paddle + $this->size / 2.0)) / ($this->size / 2.0);

      $Ball->x = $x;
      $Ball->dx = - $Ball->dx;
      $Ball->dy = $offset * ($this->Canvas->rows * 0.7);
   }
}
