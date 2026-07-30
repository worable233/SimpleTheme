# AGENTS.md

This file provides guidance to the AI agent when working with code in this repository.

## Project

WordPress theme = Vue 3 SPA (`src/`) + PHP backend (`inc/`, entry `functions.php`). Vite builds to `dist/` with `manifest: true`; WordPress reads the manifest to load hashed assets. Three build entries: `frontend` (src/main.ts), `admin` (src/admin/main.ts), `admin-shell` (src/admin/shell-entry.ts) — the admin settings panel is a separate SPA inside `src/admin/`.

## Commands

- `npm run dev` — needs a WordPress backend; set `VITE_WP_SITE_URL` in `.env`, or `VITE_USE_MOCK=true` to develop without one. `npm run dev:docker` overwrites `.env` with `.env.docker`.
- `npm run build` = type-check (`vue-tsc`) + build; `npm run build-only` skips type-check. Pre-scripts (`bin/*.mjs`) mirror committed `emojis/` → `public/emojis/` and copy illustrations & Prism assets. `public/emojis/`, `public/illustrations/`, `src/assets/illustrations/` are generated (gitignored) — edit `emojis/` or the list in `bin/copy-illustrations.mjs` instead.
- `npm run lint` runs oxlint then eslint (both with `--fix`). `npm run package` produces the theme ZIP.
- No test suite exists.

## Local WordPress (Studio)

A WordPress Studio site (`worable`, at `~/Studio/worable`) symlinks this repo as its active theme. Preview at http://localhost:8881/ (serves built `dist/`, so run `npm run build-only` to see changes there). Use `studio wp --path ~/Studio/worable <cmd>` for WP-CLI, `studio start`/`studio stop` to manage the site.

## Gotchas

- Frontend routing: catch-all route → `ContentView` resolves the URL via theme REST API (`/wp-json/simple-theme/v1/`); special pages (`/shuoshuo`, `/about`, `/archives`, `/links`) fall back to built-in Vue views on API 404.
- `inc/core/crawler-handler.php` serves full static HTML to search-engine bots instead of the SPA — keep SEO changes there, not in Vue.
- `<altcha-*>` tags are native web components (configured in vite.config.ts) — do not treat them as Vue components.
- PHP must stay compatible with PHP 7.3+ and WordPress 6.0+.
- Prettier: no semicolons, single quotes, printWidth 100.

## Commits

Conventional-commit prefixes with Chinese descriptions, e.g. `feat: 前端缓存升级为真正 LRU`.
