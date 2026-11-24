# DMS Arsip PT Indraco — Laravel 13 (rebuild)

Pintu masuk untuk semua agent (Claude Code, Antigravity, Codex, dll). Baca ini dulu, lalu ikuti urutannya.

## Mulai sesi
1. `.agents/context/progress.md` — posisi kerja, fase, langkah berikutnya, catatan handoff.
2. `.agents/context/decisions.md` — keputusan yang sudah disetujui pemilik (jangan dibuka ulang).
   - Ganti IP server: ikuti `.agents/context/runbook-ganti-ip.md` (IP sekarang `192.168.2.180`).
3. `.agents/rules/` — aturan wajib. Utama: `00-project-scope.md`, `10-safety.md`, `PROJECT_RULES.md` (+ `_SQLITE`, `_LAN_FRONTEND_OPS`), `engineering_guidelines.md`, `code_file_size_limits.md`.
4. `.agents/skills/<nama>/SKILL.md` — prosedur khusus project, dimuat sesuai tugas:
   | Skill | Untuk |
   |---|---|
   | `legacy-triage` | menilai apa yang dibawa dari Laravel 7 |
   | `rebuild-module` | membangun satu modul (struktur, langkah, catatan modul) |
   | `archive-workflow` | status arsip, penomoran box, retensi, peminjaman, BAP |
   | `sqlite-concurrency` | kode yang menulis database (banyak user) |
   | `file-upload-storage` | upload, preview, download file scan |
   | `blade-ui-components` | design system dan halaman |
   | `portable-runtime` | launcher, Caddy, php-cgi, PHP portable |
   | `behavior-parity-testing` | membandingkan hasil dengan Laravel 7 |
5. `.agents/workflows/` — `start-session`, `triage`, `rebuild-module`, `verify`, `end-session`.

## Fakta singkat
- Rebuild dari `C:\laragon\www\indraco-arsip-laravel-7` (hanya dibaca). UX dan komposisi form **sama**, tampilan baru.
- Laravel 13, PHP 8.4 NTS portable di `evironment/`, Caddy + pool php-cgi, SQLite (WAL, transaksi IMMEDIATE).
- Jalan portable di Windows lewat `START-DMS-INDRACO.bat`; 100% offline, tanpa CDN; internal LAN, tanpa SEO.
- Setiap fase/modul berhenti untuk **ACC pemilik**. Temuan dicatat dan diperbaiki. Uji di browser.

## Perintah
```sh
php artisan test                 # tes
vendor/bin/pint --test           # gaya kode
vendor/bin/phpstan analyse       # analisis statis (Larastan)
php artisan optimize             # cek cache produksi
```
PHP: pakai `evironment/php-8.4.*/php.exe` (set `PHPRC` ke folder itu) atau PHP 8.4 Laragon.

## Akhiri sesi
Jalankan `.agents/workflows/end-session.md`: perbarui progress/decisions/findings, tulis langkah berikutnya, commit `.agents/`.

===

<laravel-boost-guidelines>
=== .ai/dms-project rules ===

# DMS Arsip PT Indraco (project rules)

- Project rules, decisions and skills live in `.agents/` and override generic Laravel advice when they differ. Start with `AGENTS.md` (top of this file), then `.agents/context/progress.md`, `.agents/context/decisions.md` and `.agents/rules/`.
- Project skills: `.agents/skills/<name>/SKILL.md` (archive workflow, SQLite concurrency, rebuild-module, portable runtime, ...).
- The Laravel 7 app (`C:\laragon\www\indraco-arsip-laravel-7`) and its database are read-only references.

=== foundation rules ===

# Laravel Boost Guidelines

## Foundational Context

This application is a Laravel application running on PHP 8.4. Always use the APIs that match the installed major version of each package — do not assume a version.

Before relying on a package's API, confirm its installed version:
- PHP packages: run `composer show --direct` to list direct dependencies with versions, or `composer show <vendor/package>` for a single package.
- JS packages: check `package.json` for the installed versions.

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If a frontend change doesn't show in the UI or you get a "Unable to locate file in Vite manifest" error, run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Use `search-docs` before changes that depend on Laravel ecosystem APIs, behavior, configuration, or version-specific syntax. Skip it for copy-only edits and other changes where package documentation is irrelevant. Reuse sufficient results already in context instead of searching again.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Project Rules

- This project contains committed, area-grouped rules in `.ai/rules` when that directory exists, including path-scoped framework guidelines under `.ai/rules/boost`. Before you enter plan mode or create/edit any file, you MUST first: open @.ai/rules/index.md (it maps file globs to rule files), read every rule file whose globs cover the path(s) in scope, and run `grep -rin 'keyword' .ai/rules` to catch what a path match alone misses. Do not write code until you have read and are following every matching rule. If `.ai/rules` does not exist, continue without it.

## Artisan

- Run Artisan commands directly via the command line (e.g., `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan route:list`). Use `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan list` to discover available commands and `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan [command] --help` to check parameters.
- Inspect routes with `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan config:show app.name`, `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan list` and check their parameters with `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan [command] --help`.
- If you're creating a generic PHP class, use `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

# PHPUnit

- This project uses PHPUnit. Create tests with `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan make:test --phpunit {name}`.
- Do not include the test suite directory in `{name}`. Use `SomeFeatureTest`, not `Feature/SomeFeatureTest`.
- Read the `testing-best-practices` skill for guidance on coverage, naming, structure, dependency isolation, and review.

## Running Tests

- Run the narrowest set of tests that covers the change. Pass a file path or `--filter=testName` to `evironment\php-8.4.28-nts-Win32-vs17-x64\php.exe artisan test --compact`.
- Rerun a test after each change to it.
- Run `vendor/bin/phpunit` to call the test runner directly. It accepts the same file path and `--filter=testName` arguments.

</laravel-boost-guidelines>
