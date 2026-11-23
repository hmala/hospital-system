---
name: refactoring-clean-code
description: Safe, behavior-preserving refactoring and clean code standards for production backends and full-stack systems. Covers SOLID principles, DRY, KISS, YAGNI, code smells detection, extracting methods/classes, reducing cyclomatic complexity, keeping automated tests green, and running Laravel Pint. Use when refactoring existing code, cleaning up technical debt, improving readability, or simplifying complex logic.
metadata:
  origin: hospital-system-core
---

# Refactoring & Clean Code Standards

Guidelines, patterns, and workflows for safe, behavior-preserving code refactoring and clean code enforcement.

## Core Clean Code Principles

1. **Readability First**: Code is read 10x more often than it is written. Variable, method, and class names must reveal intent clearly without needing redundant comments.
2. **KISS (Keep It Simple, Stupid)**: The simplest design that solves the problem is best. Do not over-engineer or build premature abstractions.
3. **DRY (Don't Repeat Yourself)**: Eliminate duplicate logic and magic values. Consolidate into reusable helper methods, services, or constants.
4. **YAGNI (You Aren't Gonna Need It)**: Do not implement features or parameters based on speculative future requirements.
5. **Single Responsibility (SRP)**: Each class, controller, and method should have one clear responsibility and reason to change.
6. **Boy Scout Rule**: Leave the codebase cleaner than you found it with small, surgical, non-breaking improvements.

---

## Behavior-Preserving Refactoring Workflow

Refactoring means **improving code structure without changing observable behavior**.

```
Step 1: Test Baseline Verification
   │   Confirm existing tests pass (`php artisan test`). If test coverage is missing,
   │   write characterization tests first.
   ▼
Step 2: Plan Surgical Changes
   │   Identify specific code smells (long method, deep nesting, god controller).
   ▼
Step 3: Incremental Transformation
   │   Apply one small change at a time (Extract Method, Rename, Introduce DTO).
   ▼
Step 4: Keep Tests Green
   │   Re-run tests after each small change. Never proceed with failing tests.
   ▼
Step 5: Code Style & Static Analysis
   │   Run `vendor/bin/pint --dirty` to ensure clean, consistent formatting.
```

---

## Common Code Smells & Proven Solutions

### 1. Long Methods / High Cyclomatic Complexity
- **Smell**: Methods with > 30-40 lines, deeply nested `if/else` ladders, or multiple loops.
- **Solution**:
  - Extract early returns / guard clauses.
  - Extract cohesive sub-operations into private helper methods with descriptive names.
  - Use PHP 8.2+ `match` expressions instead of long `switch` statements or nested conditionals.

### 2. Fat / God Controllers
- **Smell**: Controllers containing database queries, business transactions, and PDF/Excel generation in one action.
- **Solution**:
  - Keep controllers thin (validate via Form Request ➔ invoke Service/Action ➔ return View/Resource).
  - Move domain calculations and workflows into dedicated Service classes (e.g., `CcuBillingService`, `NicuBillingService`).

### 3. Primitive Obsession & Array Soup
- **Smell**: Passing raw associative arrays with unstructured keys across multiple classes.
- **Solution**:
  - Use strongly typed Data Transfer Objects (DTOs) or dedicated Value Objects.
  - Use Laravel 12 Model `casts()` method for structured fields, enums, and dates.

### 4. Magic Numbers & Hardcoded Strings
- **Smell**: Direct numeric constants (e.g., `0.10`, `1`, `43`) scattered in business logic.
- **Solution**:
  - Define class constants, configuration files, or dedicated enums (e.g., `PaymentMethod::CASH`, `SurgeryStatus::COMPLETED`).

---

## PHP 8.2+ & Laravel 12 Specific Clean Code

- **Strict Types & Declarations**: Always declare parameter and return types on all methods.
- **Modern Model Casts**: Use the `casts()` method instead of the legacy `$casts` property in Eloquent models.
- **Eloquent Scopes**: Encapsulate reusable query logic into local model scopes (e.g., `scopeActive()`, `scopePaid()`).
- **No Direct Environment Calls**: Never use `env()` outside config files; always use `config('...')`.
- **Formatting Rule**: Always verify code formatting via `vendor/bin/pint --dirty` before completing any refactor.
