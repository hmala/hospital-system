# Laravel Boost & Anti-Hallucination Rules (قواعد منع هلوسة الكود وضبط الجودة)

## 1. Zero Hallucination Protocol (بروتوكول منع الهلوسة الصارم)
- **NO GUESSING SCHEMA**: Never guess database column names, foreign keys, or Eloquent relationships. Always inspect the model in `app/Models/` or check database migrations/schema before writing queries.
- **NO DEPRECATED SYNTAX**: Strictly write **Laravel 12** code:
  - Do NOT create or edit files in `app/Http/Middleware/` (Middleware is registered in `bootstrap/app.php`).
  - Do NOT create `app/Console/Kernel.php` (Console commands auto-register from `app/Console/Commands/` or `routes/console.php`).
  - Use the `casts()` method on models, not the legacy `$casts = [...]` property.
  - Never call `env()` outside `config/` files. Always use `config('...')`.
  - Use Tailwind CSS v4 conventions (`@theme`, CSS-first, modern utility names like `shrink-*` not `flex-shrink-*`).
- **NO ORM BYPASSING**: Use Eloquent models and relationships (`Model::query()`) instead of raw `DB::` queries to prevent SQL injection, ambiguous column clashes, and broken state.

## 2. Surgical Precision & Code Preservation (قاعدة التعديل الجراحي)
- **SURGICAL REPLACEMENTS ONLY**: Never overwrite an entire 500+ line controller or Blade view to modify a single function. Always use targeted replacements (`replace_file_content` or `multi_replace_file_content`).
- **PRESERVE EXISTING LOGIC**: Do not remove existing docstrings, Arabic comments, or working clinical logic unless explicitly requested.

## 3. Mandatory Verification Loop (حلقة التحقق الإلزامية بعد أي تعديل)
- After making code changes:
  1. Check code formatting: Run `vendor/bin/pint --dirty` to ensure PSR-12 and Laravel Pint standards.
  2. Verify regressions: Run relevant automated tests: `php artisan test --filter=TestName`.
  3. Inspect logs if a command fails: Check `storage/logs/laravel.log` or use `php artisan boost:mcp` diagnostics.
