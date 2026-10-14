---
name: deploy
description: Deploy application to Hostinger server via SSH or trigger deploy_hostinger script.
---

# Workflow: Deploy to Hostinger

When `/deploy` or `/deploy [mode]` is invoked:
1. Ensure git working directory is clean: stage, commit, and push any uncommitted changes to `origin main`.
2. Check deployment mode:
   - Default (Mode 1): Normal Deploy (Pull Main + Migrations + Official Seeds + Cache Clear + Webhook).
   - Mode 2 (`clean`): Deploy + Clean 48 operational tables.
   - Mode 3 (`clean-only`): Clean operational tables only without full deploy.
3. If user provided a mode parameter (e.g. `/deploy 1` or `/deploy clean`), execute corresponding SSH command or launch `deploy_hostinger.bat`.
4. If SSH is executed directly:
   - Target Host: `92.113.18.46` Port `65002` User `u175868711` Path `domains/hinpa.icu/public_html/hearmz`.
   - Run git fetch, reset hard origin/main, migrate, seed, cache-reset, optimize:clear.
5. Report deployment status and result to the user.
