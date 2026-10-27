# AGENTS.md

Notes for coding agents working on this repository. Human-facing docs are in
`docs/`, contribution rules in `CONTRIBUTING.md`.

## Project

Plenum, a forum application for WoltLab Suite Core (WSC) 6.2. Package
`com.tinsever.forum`, application directory and namespace `forum`, tables
`forum1_*`. The display name is "Plenum", but the identifier, namespace and
table prefix stay `forum` so existing installs can update. The account switcher
in `account-switcher/` is a separate WCF plugin.

WoltLab's own sources are in `vendor-ref/` (fetched by
`scripts/fetch-vendor.sh`). Check them before guessing how a core API works.

## Commands

| Task | Command |
| --- | --- |
| Lint | `./scripts/lint.sh` |
| Missing phrases | `./scripts/check-phrases.sh` |
| Regenerate language and permission files | `python3 scripts/gen/language.py && python3 scripts/gen/permissions.py` |
| Copy PHP, JS, CSS, templates into dev | `./scripts/dev-sync.sh` |
| Re-apply XML PIPs without data loss | `python3 scripts/dev_sync_pips.py http://localhost/wsc/ <pip> ...` |
| Build packages | `./scripts/build-package.sh` |
| Start dev server | `./scripts/dev-server.sh start` |

Do not run `scripts/dev-reinstall.sh` unless asked: it drops the dev database,
which may hold test data.

## Rules

- Never edit `forum/language/*.xml`, `account-switcher/language/*.xml` or the
  generated permission XML by hand. Edit `scripts/gen/` and regenerate.
- Phrases need English and German (formal "Sie") and at least three name
  segments.
- When a release removes files, templates, pages or options, list them in
  `fileDelete.xml`, `templateDelete.xml` or the matching `<delete>` block. PIPs
  never remove stale entries on their own.
- Keep UI text minimal. Do not add labels, counts or explanations nobody asked
  for.

## WSC gotchas

- `{include}` is inlined at compile time: recursive templates loop, and include
  parameters leak into later `TemplateEngine::render()` calls on the same page.
  Pass every variable explicitly.
- Application classes are not autoloadable inside the application's own install
  script. Use plain SQL there.
- ACP menu items come from the `ItemCollecting` event, not `acpMenu.xml`.
- ACP modification log entries need `wcf.acp.modificationLog.<objectType>.<action>`
  phrases.
- API routes need at least two path segments, otherwise WSC answers
  `missing_endpoint`. After changing endpoints in dev, delete
  `cache/cache.wcf_ApiEndpointCacheBuilder-*.php`.
- `Psr15DialogForm` success responses must be `{result: {...}}` for the JS to
  read them.
- Outside list views nothing forwards interaction menu clicks. Dispatch
  `interaction:execute` yourself.
- `SessionHandler::changeUser()` deletes the session. Only keys registered via
  `PreserveVariablesCollecting` survive.
- The box PIP does not re-apply `visibilityExceptions` to existing boxes.
- Child menu items must not repeat `<menu>`.
- `wcf1_import_mapping.importHash` is `char(8)`.
