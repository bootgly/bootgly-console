<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright (c) 2023-present Rodrigo de Araujo Vieira Tecnologia da Informação LTDA and Bootgly contributors
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */

namespace Invaders;


use function array_values;
use function count;
use function intdiv;
use function max;
use function min;
use function random_int;
use function round;

use Bootgly\CLI\Terminal\Input;
use Bootgly\CLI\Terminal\Output;
use Console\Games;
use Console\Games\Scenes\Scene;
use Console\Games\Timer;
use Console\Games\Vector;
use Console\Games\Zone;


/**
 * Invaders — Console platform Games module demo (Sprites + 2D math).
 *
 * A formation of sprite-sheet aliens marches sideways, descends at the
 * borders and accelerates as it shrinks (a mutable Timer interval). Move
 * with ←/→, fire with Space (one-shot cooldown Timer), dodge the bombs —
 * Vectors integrate every projectile and Zones resolve every collision.
 * Endless waves; 3 lives; `q` quits, Enter starts / restarts.
 */
class Invaders extends Games
{
   // ! ANSI styles
   private const string BORDER = "\e[90m";
   private const string TITLE = "\e[1;33m";
   private const string SHOT = "\e[1;37m";
   private const string BOMB = "\e[1;31m";

   // ! Formation grid (logical pixels per cell: 3×2 sprite + gaps)
   private const int STRIDE_X = 5;
   private const int STRIDE_Y = 3;
   /** Alien sprite per formation row */
   private const array ROWS = ['alienA', 'alienB', 'alienC'];


   // * Config
   /** Ship step per keystroke impulse (logical pixels — scaled at construct) */
   public float $step = 2.0;
   /** Seconds between player shots */
   public float $cooldown = 0.35;
   /** March interval at full formation (shrinks per wave) */
   public float $slowest = 0.75;
   /** March interval floor */
   public float $fastest = 0.12;
   /** Aliens per formation row (scaled at construct) */
   public int $wide = 6;
   /** Formation rows (scaled at construct) */
   public int $tall = 3;

   // * Data
   /** Ship top-left corner (y is fixed) */
   public Vector $Ship;
   /** Formation origin (top-left alien cell) */
   public Vector $Formation;
   /** March direction (+1 = right) */
   public int $direction = 1;
   /** @var array<int,array{0:int,1:int}> Alive aliens (formation grid col, row) */
   public array $aliens = [];
   /** @var array<int,Bolt> Player shots (moving up) */
   public array $shots = [];
   /** @var array<int,Bolt> Alien bombs (moving down) */
   public array $bombs = [];
   /** @var array<int,array{Sprite:\Console\Games\Sprite,Timer:Timer,x:int,y:int}> Explosions */
   public array $booms = [];
   /** March cadence (interval mutates as the formation shrinks) */
   public Timer $March;
   /** Player fire cooldown (one-shot — expired = ready) */
   public Timer $Fire;
   /** Alien bomb cadence */
   public Timer $Raid;
   /** Ship movement range */
   public Zone $Field;

   // * Metadata
   public private(set) int $score = 0;
   public private(set) int $lives = 3;
   public private(set) int $wave = 1;
   /** Full formation size (acceleration denominator) */
   private int $total = 18;


   public function __construct (null|Input $Input = null, null|Output $Output = null)
   {
      parent::__construct($Input, $Output, columns: 40, rows: 26, aspect: 2);

      // ! Board size (terminal-fitted by the Games shell)
      $columns = $this->Canvas->columns;
      $rows = $this->Canvas->rows;

      // * Config — scale the gameplay to the board
      $this->step = max(1.0, $columns / 20.0);
      $this->wide = max(3, min(6, intdiv($columns - 10, self::STRIDE_X)));
      $this->tall = $rows >= 22 ? 3 : 2;

      // * Data
      $this->March = new Timer($this->slowest);
      $this->Fire = new Timer($this->cooldown, repeat: false);
      $this->Raid = new Timer(1.2);
      $this->Field = new Zone(1.0, (float) ($rows - 3), (float) ($columns - 7), 0.0);

      // * Metadata
      $this->total = $this->wide * $this->tall;

      // @ Sprite sheet
      $this->Sprites->load(__DIR__ . '/Invaders.sprites.php');

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
            $center = intdiv($this->Canvas->columns, 2);

            $this->Canvas->clear();
            $this->frame();

            // @ Decorative alien row (one sprite per formation color)
            foreach (self::ROWS as $offset => $alien) {
               $this->Sprites->get($alien)->stamp($this->Canvas, $center - 8 + $offset * 6, $middle - 6);
            }

            $this->Canvas->center($middle - 2, 'INVADERS', self::TITLE);
            $this->Canvas->center($middle, 'Powered by the Bootgly Console platform', self::BORDER);
            $this->Canvas->center($middle + 2, '[Enter] play    [←/→] move    [Space] fire    [q] quit');
         }
      ));
      $this->Scenes->add(new Scene(
         'Play',
         update: function (float $delta): void {
            $this->play($delta);
         },
         render: function (): void {
            $this->Canvas->clear();
            $this->frame();

            // @ Formation (shared sprites — lockstep march frame)
            $ox = $this->Formation->x;
            $oy = $this->Formation->y;
            foreach ($this->aliens as [$col, $row]) {
               $this->Sprites->get(self::ROWS[$row % 3])->stamp(
                  $this->Canvas,
                  (int) round($ox + $col * self::STRIDE_X),
                  (int) round($oy + $row * self::STRIDE_Y)
               );
            }

            // @ Ship
            $this->Sprites->get('ship')->stamp(
               $this->Canvas, (int) round($this->Ship->x), (int) round($this->Ship->y)
            );

            // @ Projectiles
            foreach ($this->shots as $Shot) {
               $this->Canvas->plot(
                  (int) round($Shot->Position->x), (int) round($Shot->Position->y), '│ ', self::SHOT
               );
            }
            foreach ($this->bombs as $Bomb) {
               $this->Canvas->plot(
                  (int) round($Bomb->Position->x), (int) round($Bomb->Position->y), '▼ ', self::BOMB
               );
            }

            // @ Explosions
            foreach ($this->booms as $boom) {
               $boom['Sprite']->stamp($this->Canvas, $boom['x'], $boom['y']);
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

            $this->Canvas->center($middle - 1, ' GAME OVER ', self::BOMB);
            $this->Canvas->center($middle + 1, "Score: {$this->score} — wave {$this->wave}", self::SHOT);
            $this->Canvas->center($middle + 3, '[Enter] play again    [q] quit');
         }
      ));

      $this->Scenes->switch('Menu');
      $this->reset();
   }

   /**
    * Reset the match: score, lives, wave, ship and a fresh formation.
    */
   public function reset (): void
   {
      // * Config
      $this->slowest = 0.75;

      // * Metadata
      $this->score = 0;
      $this->lives = 3;
      $this->wave = 1;

      // @
      $this->Ship = new Vector(
         ($this->Canvas->columns - 5) / 2.0,
         (float) ($this->Canvas->rows - 3)
      );
      $this->spawn();
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
      $this->Statusbar->left = ['Invaders', "Score: {$this->score}", "Lives: {$this->lives}", "Wave: {$this->wave}"];
      $this->Statusbar->right = ['[q] quit'];
   }

   /**
    * Spawn a fresh formation: full grid, centered origin, rearmed timers.
    */
   private function spawn (): void
   {
      // * Data
      $this->aliens = [];
      for ($row = 0; $row < $this->tall; $row++) {
         for ($col = 0; $col < $this->wide; $col++) {
            $this->aliens[] = [$col, $row];
         }
      }
      $this->Formation = new Vector(
         (float) intdiv($this->Canvas->columns - ($this->wide * self::STRIDE_X - 2), 2),
         2.0
      );
      $this->direction = 1;
      $this->shots = [];
      $this->bombs = [];
      $this->booms = [];

      // @ Rearm the cadences — the fire cooldown pre-arms (first shot is instant)
      $this->March->interval = $this->slowest;
      $this->March->reset();
      $this->Raid->reset();
      $this->Fire->reset();
      $this->Fire->tick($this->Fire->interval);
   }

   /**
    * Advance the formation one march step: move, descend at the borders and
    * flip the alien sprites in lockstep.
    */
   private function march (): void
   {
      // ! Alive formation extent (grid columns)
      $first = $this->wide;
      $last = 0;
      foreach ($this->aliens as [$col, $row]) {
         $first = min($first, $col);
         $last = max($last, $col);
      }

      // ! Formation pixel edges after the next step
      $left = $this->Formation->x + $first * self::STRIDE_X + $this->direction;
      $right = $this->Formation->x + $last * self::STRIDE_X + 3.0 + $this->direction;

      // ?: Border reached — descend and reverse; otherwise step sideways
      if (($this->direction > 0 && $right > $this->Canvas->columns - 1) || ($this->direction < 0 && $left < 1.0)) {
         $this->Formation->y += 1.0;
         $this->direction = -$this->direction;
      }
      else {
         $this->Formation->x += $this->direction;
      }

      // @ Lockstep animation — one frame write per alien row color
      $frame = ($this->Sprites->get('alienA')->frame + 1) % 2;
      foreach (self::ROWS as $alien) {
         $this->Sprites->get($alien)->frame = $frame;
      }
   }

   /**
    * Whether the formation reached the ship (invasion — instant game over).
    */
   private function invade (): bool
   {
      // ? Empty formation cannot invade
      if (count($this->aliens) === 0) {
         return false;
      }

      // ! Alive formation extent (pixels)
      $first = $this->wide;
      $last = 0;
      $bottom = 0;
      foreach ($this->aliens as [$col, $row]) {
         $first = min($first, $col);
         $last = max($last, $col);
         $bottom = max($bottom, $row);
      }

      $Extent = new Zone(
         $this->Formation->x + $first * self::STRIDE_X,
         $this->Formation->y,
         ($last - $first) * self::STRIDE_X + 3.0,
         $bottom * self::STRIDE_Y + 2.0
      );
      $Hull = new Zone($this->Ship->x, $this->Ship->y, 5.0, 2.0);

      // : Overlapping the ship or descending past it
      return $Extent->check($Hull) === true
         || $Extent->y + $Extent->height >= $this->Ship->y + 2.0;
   }

   /**
    * Advance one Play tick: ship, timers, projectiles, collisions, waves.
    */
   private function play (float $delta): void
   {
      $columns = $this->Canvas->columns;
      $rows = $this->Canvas->rows;

      // @ Ship: one impulse per queued keystroke — a tap is one precise step,
      // holding streams the terminal auto-repeats (capped per tick)
      $moves = 0;
      while ($moves < 3 && $this->Keyboard->pop('LEFT') === true) {
         $this->Ship->x -= $this->step;
         $moves++;
      }
      while ($moves < 3 && $this->Keyboard->pop('RIGHT') === true) {
         $this->Ship->x += $this->step;
         $moves++;
      }
      $this->Field->clamp($this->Ship);

      // @ Fire: one-shot cooldown — expired = ready
      $this->Fire->tick($delta);
      if ($this->Keyboard->pop('SPACE') === true && $this->Fire->expired === true) {
         $this->shots[] = new Bolt(
            new Vector($this->Ship->x + 2.0, $this->Ship->y - 1.0),
            new Vector(0.0, -$rows * 0.9)
         );
         $this->Fire->reset();
      }

      // @ Bombs: a random alive alien drops one per raid cycle
      if ($this->Raid->tick($delta) === true && count($this->aliens) > 0) {
         [$col, $row] = $this->aliens[random_int(0, count($this->aliens) - 1)];

         $this->bombs[] = new Bolt(
            new Vector(
               $this->Formation->x + $col * self::STRIDE_X + 1.5,
               $this->Formation->y + $row * self::STRIDE_Y + 2.0
            ),
            new Vector(0.0, $rows * 0.45)
         );
      }

      // @ March cadence — the interval shrinks as the formation shrinks
      if ($this->March->tick($delta) === true) {
         $this->march();

         // ? Invasion — the formation reached the ship
         if ($this->invade() === true) {
            $this->Scenes->switch('Over');

            return;
         }
      }

      // @@ Player shots: integrate, cull off-board, collide with the formation
      $shots = [];
      foreach ($this->shots as $Shot) {
         $Shot->Position->add($Shot->Velocity, $delta);

         // ? Shot left the board
         if ($Shot->Position->y < 1.0) {
            continue;
         }

         // ? Shot hit an alien
         $hit = false;
         foreach ($this->aliens as $index => [$col, $row]) {
            $Target = new Zone(
               $this->Formation->x + $col * self::STRIDE_X,
               $this->Formation->y + $row * self::STRIDE_Y,
               3.0,
               2.0
            );

            if ($Target->contain($Shot->Position) === true) {
               $this->blast($index, $Target);
               $hit = true;

               break;
            }
         }
         if ($hit === true) {
            continue;
         }

         $shots[] = $Shot;
      }
      $this->shots = $shots;

      // ? Wave cleared — respawn a faster formation
      if (count($this->aliens) === 0) {
         $this->wave++;
         $this->slowest = max($this->fastest, $this->slowest * 0.9);
         $this->spawn();

         return;
      }

      // @@ Bombs: integrate, cull off-board, collide with the ship
      $Hull = new Zone($this->Ship->x, $this->Ship->y, 5.0, 2.0);
      $bombs = [];
      foreach ($this->bombs as $Bomb) {
         $Bomb->Position->add($Bomb->Velocity, $delta);

         // ? Bomb left the board
         if ($Bomb->Position->y > $rows - 2.0) {
            continue;
         }

         // ? Bomb hit the ship
         if ($Hull->contain($Bomb->Position) === true) {
            $this->lives--;
            $this->booms[] = [
               'Sprite' => clone $this->Sprites->get('boom'),
               'Timer' => new Timer(0.4, repeat: false),
               'x' => (int) round($this->Ship->x + 1.0),
               'y' => (int) round($this->Ship->y)
            ];

            // ? No lives left
            if ($this->lives <= 0) {
               $this->Scenes->switch('Over');

               return;
            }

            continue;
         }

         $bombs[] = $Bomb;
      }
      $this->bombs = $bombs;

      // @@ Explosions: flicker on wall time, expire on their TTL
      $booms = [];
      foreach ($this->booms as $boom) {
         $boom['Sprite']->tick($delta);

         if ($boom['Timer']->tick($delta) === false) {
            $booms[] = $boom;
         }
      }
      $this->booms = $booms;
   }

   /**
    * Blast an alien: remove it, score, explode and speed the march up.
    *
    * @param int $index The alien index in the alive list.
    * @param Zone $Target The alien hitbox (explosion anchor).
    */
   private function blast (int $index, Zone $Target): void
   {
      // @ Remove and reindex — the alive list must stay contiguous
      // (the bomb picker draws by numeric index)
      unset($this->aliens[$index]);
      $this->aliens = array_values($this->aliens);

      // * Metadata
      $this->score += 10;

      // @ Explosion — an independent clone with its own flicker and TTL
      $this->booms[] = [
         'Sprite' => clone $this->Sprites->get('boom'),
         'Timer' => new Timer(0.4, repeat: false),
         'x' => (int) round($Target->x),
         'y' => (int) round($Target->y)
      ];

      // @ March acceleration — fewer aliens, shorter interval
      $this->March->interval = $this->fastest
         + ($this->slowest - $this->fastest) * count($this->aliens) / $this->total;
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
