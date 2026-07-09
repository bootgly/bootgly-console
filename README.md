<p align="center">
  <img src="https://github.com/bootgly/.github/raw/main/bootgly-logo.128x128.jpg" alt="bootgly-logo" width="120px" height="120px"/>
</p>
<h1 align="center">Bootgly Console</h1>
<p align="center">
  <i>Bootgly Console Platform.</i>
</p>
<p align="center">
  <a href="https://packagist.org/packages/bootgly/bootgly-console">
    <img alt="Bootgly License" src="https://img.shields.io/github/license/bootgly/bootgly-console"/>
    <!--
    </br>
    <img alt="Github Actions - Bootgly Workflow" src="https://img.shields.io/github/actions/workflow/status/bootgly/bootgly/bootgly.yml?label=bootgly"/>
    <img alt="Github Actions - Docker Workflow" src="https://img.shields.io/github/actions/workflow/status/bootgly/bootgly/docker.yml?label=docker"/>-->
  </a>
</p>

> Bootgly Console Platform composed by the CLI interface.

The **opinionated TUI layer** over `Bootgly\CLI`: an application shell for full-screen terminal apps and a module for terminal games. Everything it wires remains plain CLI underneath.

## Getting started

Use the **canonical installer** — it sets up a [bootgly.kit](https://github.com/bootgly/bootgly.kit) workspace, where the platforms are unified, and asks which ones to enable (pick **Console**):

```bash
curl -fsSL https://bootgly.com/install | bash
```

From the kit, the project wizard imports this platform's demo projects (**Import projects from Platforms → Console**):

```bash
php bootgly project create
```

> ⚠️ Using this repository directly is **discouraged** — `bootgly.kit` is the starting point: it is where the Bootgly core and the platforms are mounted and booted together. See [Getting started](https://docs.bootgly.com/guide/getting-started). Cloning `bootgly-console` standalone is only meant for developing the platform itself.

## Modules

- **`Console\App`** — the TUI application shell: terminal lifecycle (alternate screen, raw input, resize, restore-on-exit), Screens + Router navigation, Keymaps with chords, Statusbar, Toasts, command Palette and log Tail.
- **`Console\Games`** — the game shell over App: fixed-timestep Loop, diff-rendered Canvas, held-key Keyboard heuristics, Scenes, Sprite sheets and 2D math (Vector, Zone).

## Demo projects (exportable)

| Project    | Shows |
|------------|-------|
| `Snake`    | Classic Snake — Games module basics: loop, canvas, held-key steering |
| `Pong`     | Pong vs AI — paddles, ball physics, scenes |
| `Invaders` | Space Invaders — sprite sheets and 2D math (hitboxes) |

After importing them in the kit:

```bash
php bootgly project Snake start
```

## Developing the platform

Only for working on `bootgly-console` itself (with the `bootgly` core as a sibling checkout):

```bash
./bootgly test                                # test suites
vendor/bin/phpstan analyse -c @/phpstan.neon  # static analysis
./bootgly project Snake start                 # run a demo
```

[Documentation][PROJECT_DOCS] — see the *Console Platform* guide and the *Console* manual pages.



<!-- Links -->
[PROJECT_DOCS]: https://docs.bootgly.com/
[GITHUB_MAIN_REPOSITORY]: https://github.com/bootgly/bootgly/
[GITHUB_ORG_SPONSOR]: https://github.com/sponsors/bootgly/
