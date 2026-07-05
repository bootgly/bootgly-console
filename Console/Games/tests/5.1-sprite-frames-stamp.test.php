<?php

namespace Console\Games;

use function assert;
use function fopen;
use function ftruncate;
use function rewind;
use function stream_get_contents;

use Bootgly\ACI\Tests\Suite\Test\Specification;
use Bootgly\CLI\Terminal\Output;

use Console\Games\Canvas\Modes;

return new Specification(
   description: 'It should parse WYSIWYG frames, stamp with transparency and animate on both paths',
   test: function () {
      // ! Canvas 6×3 logical pixels (aspect 1) on a memory stream
      $stream = fopen('php://memory', 'r+');
      $Output = new Output($stream);
      $Canvas = new Canvas($Output, 6, 3);

      $grab = function () use ($stream): string {
         rewind($stream);
         $data = (string) stream_get_contents($stream);
         ftruncate($stream, 0);
         rewind($stream);

         return $data;
      };

      // ! Two-frame sprite — the trailing space in frame 0 is transparent
      $Sprite = new Sprite('mark', frames: ["AB\nC ", "XY\nZ "]);

      yield assert(
         assertion: $Sprite->width === 2 && $Sprite->height === 2,
         description: 'width/height measure the widest row and tallest frame'
      );

      // @ Frame 0 stamp: pixels plot, the alpha pixel is skipped
      $Sprite->stamp($Canvas, 1, 0);
      $Canvas->flush();

      yield assert(
         assertion: $grab() === "\e[1;1H AB   \e[2;1H C    \e[3;1H      ",
         description: 'stamp() plots frame 0 and skips transparent pixels'
      );

      // @ Step-driven flip: assigning $frame switches the stamped frame
      $Sprite->frame = 1;
      $Canvas->clear();
      $Sprite->stamp($Canvas, 1, 0);
      $Canvas->flush();

      yield assert(
         assertion: $grab() === "\e[1;2HXY\e[2;2HZ",
         description: 'Assigning $frame flips the animation (step-driven path)'
      );

      // @ Wall-time path: tick() advances whole frames and carries the remainder
      $Anim = new Sprite('anim', frames: ['A', 'B'], FPS: 2.0);
      $Anim->tick(0.3);
      yield assert(
         assertion: $Anim->frame === 0,
         description: 'tick() below the frame period does not advance'
      );
      $Anim->tick(0.3);
      yield assert(
         assertion: $Anim->frame === 1,
         description: 'tick() advances when the accumulated clock crosses 1/FPS'
      );

      // @ FPS 0.0 = step-driven only: tick() is a no-op
      $Sprite->tick(10.0);
      yield assert(
         assertion: $Sprite->frame === 1,
         description: 'tick() is a no-op when FPS is 0.0'
      );

      // @ Styled pixels wrap in the sprite style + reset
      $Canvas->reset();
      $Canvas->clear();
      (new Sprite('dot', frames: ['*'], style: "\e[32m"))->stamp($Canvas, 0, 0);
      $Canvas->flush();

      yield assert(
         assertion: $grab() === "\e[1;1H\e[32m*\e[0m     \e[2;1H      \e[3;1H      ",
         description: 'Sprite style wraps every plotted pixel'
      );

      // @ Off-canvas pixels clip silently (plot OOB guard)
      $Canvas->clear();
      $Sprite->stamp($Canvas, 5, 2);
      $Canvas->flush();
      $partial = $grab();

      yield assert(
         assertion: $partial !== '',
         description: 'Partially off-canvas stamps still paint the visible pixels'
      );

      // @ Aspect 2: single-char sprite pixels double on screen
      $wide = fopen('php://memory', 'r+');
      $Wide = new Canvas(new Output($wide), 4, 2, Modes::Block, aspect: 2);
      (new Sprite('px', frames: ['█']))->stamp($Wide, 1, 0);
      $Wide->flush();
      rewind($wide);

      yield assert(
         assertion: (string) stream_get_contents($wide) === "\e[1;1H  ██    \e[2;1H        ",
         description: 'Sprite pixels double through the Canvas aspect'
      );
   }
);
