<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Snake;


use function array_pop;
use function array_unshift;
use function in_array;
use function intdiv;

use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Output;
use Console\Games;
use Console\Games\Scenes\Scene;


/**
 * Classic Snake — the Console platform Games module demo.
 *
 * Scenes: Menu → Play → Over. Arrows steer, holding an arrow accelerates
 * (auto-repeat detection), `q` quits, Enter starts / restarts.
 */
class Snake extends Games
{
   // ! ANSI styles
   private const string BORDER = "\e[90m";
   private const string BODY = "\e[32m";
   private const string HEAD = "\e[1;32m";
   private const string FOOD = "\e[1;31m";


   // * Config
   /** Snake initial length */
   public int $length = 5;

   // * Data
   public Food $Food;
   /** @var array<int,array{0: int, 1: int}> Body cells — head first */
   public array $body = [];
   public string $direction = 'RIGHT';

   // * Metadata
   public private(set) int $score = 0;


   public function __construct (null|Input $Input = null, null|Output $Output = null)
   {
      parent::__construct($Input, $Output, columns: 60, rows: 30, aspect: 2);

      // * Data
      $this->Food = new Food;

      // @ Game pacing
      $this->Loop->tps = 12;

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
            $this->Canvas->center($middle - 2, 'CLASSIC SNAKE GAME', self::HEAD);
            $this->Canvas->center($middle, 'Powered by the Bootgly Console platform', self::BORDER);
            $this->Canvas->center($middle + 2, '[Enter] play    [arrows] steer    [q] quit');
         }
      ));
      $this->Scenes->add(new Scene(
         'Play',
         update: function (float $delta): void {
            $this->steer();

            // @ Base step + held-arrow acceleration
            $steps = $this->Keyboard->check($this->direction) === true ? 2 : 1;
            for ($step = 0; $step < $steps; $step++) {
               if ($this->move() === false) {
                  $this->Scenes->switch('Over');

                  return;
               }
            }
         },
         render: function (): void {
            $this->Canvas->clear();
            $this->frame();

            // @ Food
            $this->Canvas->plot($this->Food->x, $this->Food->y, '● ', self::FOOD);

            // @ Snake (head first)
            foreach ($this->body as $index => $cell) {
               $index === 0
                  ? $this->Canvas->plot($cell[0], $cell[1], '█', self::HEAD)
                  : $this->Canvas->plot($cell[0], $cell[1], '▓', self::BODY);
            }
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

            $this->Canvas->center($middle - 1, ' GAME OVER ', self::FOOD);
            $this->Canvas->center($middle + 1, '[Enter] play again    [q] quit');
         }
      ));

      $this->Scenes->switch('Menu');
      $this->reset();
   }

   /**
    * Reset the round: body, direction, score and food.
    */
   public function reset (): void
   {
      // * Data
      $this->direction = 'RIGHT';
      $this->body = [];
      $x = intdiv($this->Canvas->columns, 2);
      $y = intdiv($this->Canvas->rows, 2);
      for ($offset = $this->length; $offset > 0; $offset--) {
         $this->body[] = [$x - $this->length + $offset, $y];
      }

      // * Metadata
      $this->score = 0;

      // @
      $this->Food->spawn($this->Canvas->columns, $this->Canvas->rows, $this->body);
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
      $this->Statusbar->left = ['Snake', "Score: {$this->score}"];
      $this->Statusbar->right = ['[q] quit'];
   }

   /**
    * Steer from the pending arrow presses (reversals ignored).
    */
   private function steer (): void
   {
      foreach (['UP', 'DOWN', 'LEFT', 'RIGHT'] as $arrow) {
         if ($this->Keyboard->pop($arrow) === false) {
            continue;
         }

         $reverse = match ($arrow) {
            'UP' => 'DOWN',
            'DOWN' => 'UP',
            'LEFT' => 'RIGHT',
            'RIGHT' => 'LEFT'
         };

         if ($this->direction !== $reverse) {
            $this->direction = $arrow;
         }
      }
   }

   /**
    * Advance the snake one cell.
    *
    * @return bool False on collision (game over).
    */
   private function move (): bool
   {
      // ! Next head position
      [$x, $y] = $this->body[0];
      [$x, $y] = match ($this->direction) {
         'UP' => [$x, $y - 1],
         'DOWN' => [$x, $y + 1],
         'LEFT' => [$x - 1, $y],
         default => [$x + 1, $y]
      };

      // ? Wall or body collision
      if (
         $x === 0 || $x === $this->Canvas->columns - 1
         || $y === 0 || $y === $this->Canvas->rows - 1
         || in_array([$x, $y], $this->body, true) === true
      ) {
         return false;
      }

      // @ Advance
      array_unshift($this->body, [$x, $y]);

      // ?: Eat — grow and respawn the food
      if ($x === $this->Food->x && $y === $this->Food->y) {
         $this->score++;
         $this->Food->spawn($this->Canvas->columns, $this->Canvas->rows, $this->body);

         return true;
      }

      // : Move — drop the tail
      array_pop($this->body);

      return true;
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
}
