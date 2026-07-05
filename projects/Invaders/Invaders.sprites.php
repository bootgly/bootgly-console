<?php
/*
 * --------------------------------------------------------------------------
 * Bootgly PHP Framework
 * Developed by Rodrigo Vieira (@rodrigoslayertech)
 * Copyright 2023-present
 * Licensed under MIT
 * --------------------------------------------------------------------------
 */


use Console\Games\Sprite;


// Unicode sprite sheet — 1 character = 1 logical pixel (the Canvas aspect
// doubles every pixel on screen). Spaces are transparent.
return [
   // # Aliens (2 frames — the march flips them in lockstep)
   new Sprite('alienA', frames: ["▄█▄\n▀ ▀", "▄█▄\n▝ ▘"], style: "\e[1;35m"),
   new Sprite('alienB', frames: ["▄█▄\n▀ ▀", "▄█▄\n▝ ▘"], style: "\e[1;36m"),
   new Sprite('alienC', frames: ["▄█▄\n▀ ▀", "▄█▄\n▝ ▘"], style: "\e[1;32m"),

   // # Player ship
   new Sprite('ship', frames: ["  ▲  \n▄███▄"], style: "\e[1;37m"),

   // # Explosion (wall-time flicker via FPS)
   new Sprite('boom', frames: [" ✦ \n✦ ✦", "✧ ✧\n ✧ "], style: "\e[1;33m", FPS: 8.0)
];
