# Releasing

GitHub Actions (`.github/workflows/ci.yml`) lints the code on PHP 8.1 and 8.4,
checks the generated files and builds the packages on every push. The archives
are attached to each run as an artifact.

## Publishing a release

1. Bump `<version>` in `forum/package.xml`, and in
   `account-switcher/package.xml` if it changed.
2. If the release removes files, templates or pages, list them in
   `fileDelete.xml`, `templateDelete.xml` or the `<delete>` block of `page.xml`.
   Package installation plugins never remove stale entries on their own.
3. Write `release-notes/<tag>.md`.
4. Push a tag that matches the forum version: "1.0.0 Beta 2" becomes
   `v1.0.0-beta.2`, "1.0.0" becomes `v1.0.0`.

CI then creates the release with the forum, the account switcher and the
importer attached. It uses the built-in `GITHUB_TOKEN`, no secret is needed. The
package server picks up the release within ten minutes.
