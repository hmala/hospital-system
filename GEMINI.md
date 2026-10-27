# Gemini & Antigravity Assistant Directive

## Mandatory System & Code Standards
1. **Always Read Rules First**: Refer to `.agents/rules/hospital-core.md` and `.agents/rules/laravel-boost.md`.
2. **Zero Hallucination Policy**:
   - Inspect existing models and database schemas before writing any query or Eloquent relation. Never guess column names.
   - Strictly follow Laravel 12 architecture (`bootstrap/app.php`, model `casts()` method, Form Requests for validation).
   - Never use `env()` outside configuration files.
3. **Surgical Edits**: Make focused, small changes without overwriting intact files.
4. **Automated Verification**: Run `vendor/bin/pint --dirty` and `php artisan test` after writing code to ensure zero syntax or logical errors.
5. **Git Protocol**: Direct push (`git push`) strictly to `main`.
