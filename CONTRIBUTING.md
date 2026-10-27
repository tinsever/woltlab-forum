# Contributing

Bug reports, translations and pull requests are welcome.

## Reporting bugs

Open an [issue](https://github.com/tinsever/woltlab-forum/issues) with the Plenum
and WoltLab Suite versions, the steps to reproduce and what you expected. Report
security issues privately, see [SECURITY.md](SECURITY.md).

## Pull requests

1. Set up the dev install as described in [docs/development.md](docs/development.md).
2. Keep a pull request to one change. Discuss larger features in an issue first.
3. Before you push, run:

   ```sh
   ./scripts/lint.sh
   ./scripts/check-phrases.sh
   python3 scripts/gen/language.py && python3 scripts/gen/permissions.py
   ```

4. Commit the regenerated files together with your change.

## Conventions

- **Phrases** live in `scripts/gen/phrases*.py`, never edit
  `forum/language/*.xml` by hand. Every phrase needs English and German. German
  uses the formal "Sie".
- **Permissions** live in `scripts/gen/phrases_permissions.py` and are written
  to the XML by `permissions.py`.
- **UI text** stays short and plain. No counters or hints that the user did not
  ask for.
- **Removed files** must be listed in `fileDelete.xml`, `templateDelete.xml` or
  a `<delete>` block, otherwise existing installs keep them.
- **Commit messages** follow [Conventional Commits](https://www.conventionalcommits.org/):
  `feat:`, `fix:`, `chore:`, `ci:`, `docs:`.

By contributing you agree that your work is licensed under the LGPL-2.1.
