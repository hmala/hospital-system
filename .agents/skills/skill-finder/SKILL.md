---
name: skill-finder
description: Discover, search, evaluate, and manage Agent Skills from standard customization roots, local directories, or skill registries based on user intent and task requirements.
---

# Skill Finder

Use this skill whenever the user asks to find, search, discover, install, or manage agent skills (e.g., "find a skill for X", "install skill Y", "what skills are available?").

## Core Capabilities

1. **Skill Discovery**:
   - Inspect standard customization roots:
     - Project-level: `.agents/skills/`
     - Global-level: `~/.gemini/config/skills/` or `~/.gemini/antigravity-ide/`
   - Read `skills.json` (if present) for registered external skill directories or inheritance chains.

2. **Intent Matching**:
   - Parse `SKILL.md` YAML frontmatter (`name` and `description`).
   - Compare user request against available skill triggers.
   - Load full `SKILL.md` body only after trigger match (progressive disclosure).

3. **Skill Creation & Installation**:
   - Create new skills under `.agents/skills/<skill_name>/SKILL.md`.
   - Ensure proper YAML frontmatter:
     ```yaml
     ---
     name: <skill_name>
     description: <concise summary of triggers and capabilities>
     ---
     ```
   - Set up supporting directories if needed (`scripts/`, `examples/`, `resources/`, `references/`).

4. **Skill Registry Management**:
   - Maintain `skills.json` format when linking external skill folders:
     ```json
     {
       "entries": [
         { "path": "path/to/custom/skills" }
       ],
       "inherits": [
         { "path": "path/to/shared/skills.json" }
       ],
       "exclude": ["some_skill_to_ignore"]
     }
     ```

## Workflow

1. **Listing Available Skills**:
   - Scan `.agents/skills/` directory.
   - Read frontmatter of each `SKILL.md`.
   - Present a clean markdown summary table of installed skills with names and trigger descriptions.

2. **Installing a New Skill**:
   - Create `.agents/skills/<skill_name>/` directory.
   - Write `SKILL.md` containing clear usage rules, frontmatter, and instructions.
   - Inform user that the skill is automatically discovered and ready for use.
