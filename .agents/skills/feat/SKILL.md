---
name: feat
description: Build or update a Laravel feature with zero hallucinations. Automatically inspects database schema, applies surgical code edits, and runs Pint and PHPUnit tests.
---

# Feature Build (Zero Hallucination)

Build or update a Laravel feature with zero hallucinations. Automatically inspects database schema, applies surgical code edits, and runs Pint and PHPUnit tests.

## Instructions

When `/feat [description]` or this skill is invoked:

1. **Schema & Model Inspection**:
   - Inspect the relevant model in `app/Models/` or run schema checks on the database tables involved before writing any code.
   - Never guess column names or relationships.

2. **Mechanism & Code Locations Presentation (Pre-Execution Review)**:
   - Present a clear explanation of the mechanism (الآلية) and exact code locations/file paths (أماكن الأكواد والملفات).
   - Discuss and align with the user first. Do NOT start writing or editing any code until the user approves the plan.

3. **Surgical Implementation**:
   - Follow Laravel 12 architecture (`bootstrap/app.php`, model `casts()` method, Form Requests, Tailwind v4).
   - Use targeted surgical replacements without overwriting surrounding working code.

4. **Automated Verification**:
   - Run `vendor/bin/pint --dirty` to ensure code styling.
   - Run `php artisan test --filter=[RelevantTest]` to verify there are zero regressions.

5. **Summary**:
   - Provide a concise summary of the changes and test results.

