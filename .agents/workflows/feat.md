---
name: feat
description: Build or update a Laravel feature with zero hallucinations. Automatically inspects database schema, applies surgical code edits, and runs Pint and PHPUnit tests.
---

# Workflow: Autonomous Feature Build (Zero Hallucination)

When `/feat [description]` is invoked:
1. **Schema & Model Inspection**:
   - Inspect the relevant model in `app/Models/` or run schema checks on the database tables involved before writing any code.
2. **Surgical Implementation**:
   - Follow Laravel 12 architecture (`bootstrap/app.php`, model `casts()` method, Form Requests, Tailwind v4).
   - Use targeted surgical replacements without overwriting surrounding working code.
3. **Automated Verification**:
   - Run `vendor/bin/pint --dirty` to ensure code styling.
   - Run `php artisan test --filter=[RelevantTest]` to verify there are zero regressions.
4. **Summary**:
   - Provide a concise summary of the changes and test results.
