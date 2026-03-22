# Contributing

## Local Tooling

This repository includes OpenCode-oriented workspace metadata:

- `AGENTS.md` for project rules
- `opencode.json` for shared local safety defaults
- `.opencode/commands/` for common workflows (`/ci`, `/test`, `/types`, `/lint`, `/fix`, `/review`)

Use your personal global OpenCode config or a local uncommitted `tui.json` for private preferences.

## Quality Gates

Run before opening a PR:

```bash
./vendor/bin/pest
./vendor/bin/phpstan analyse --configuration=phpstan.neon.dist
```
