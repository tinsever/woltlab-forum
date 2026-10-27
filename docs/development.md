# Development

The scripts set up a local WoltLab Suite install in developer mode, served by
PHP's built-in web server. You need PHP 8.2 (or another version Core 6.2
supports), MariaDB or MySQL, git, rsync and Python 3.

## Setup

1. Fetch the WoltLab sources into `vendor-ref/`:

   ```sh
   ./scripts/fetch-vendor.sh
   ```

2. Create a database user that may create the database `wsc`. The defaults are
   user `wsc`, password `wsc` on 127.0.0.1. Override them with `DB_HOST`,
   `DB_USER` and `DB_PASSWORD`.

3. Install everything from scratch. This **drops** the `wsc` database.

   ```sh
   ./scripts/dev-reinstall.sh
   ```

The server listens on port 80, because the WSC frontend does not accept another
port. Set `PHP_BIN` if php is not at `/opt/homebrew/opt/php@8.2/bin/php`. The
router only accepts requests from localhost.

- Frontend: http://localhost/wsc/forum/
- ACP: http://localhost/wsc/acp/
- Logins: `dev` / `root` (admin), `alice` / `alice`, `bob` / `bob`

## Scripts

| Script | Purpose |
| --- | --- |
| `scripts/dev-server.sh start\|stop` | Start or stop the web server |
| `scripts/dev-sync.sh` | Copy PHP, JS, CSS and templates into the dev install |
| `scripts/dev_sync_pips.py [url] [pip ...]` | Re-apply XML and language PIPs without losing data |
| `scripts/dev_worker.py CLASS ...` | Run rebuild-data workers |
| `scripts/install_package.py TAR` | Install or update a package archive via the ACP |
| `scripts/uninstall_package.py ID` | Uninstall a package |
| `scripts/check-import.php CORE_DIR CORE_52_INSTALL_SQL` | Full import with synthetic data into a disposable `importcheck*` install |
| `scripts/check-importer-labels.php [CORE_DIR]` | Label-import regression test (writes are rolled back) |
| `scripts/check-phrases.sh` | List phrases that are used but missing |
| `scripts/lint.sh` | PHP syntax and XML well-formedness |

## Generated files

Language files and the permission XML are generated. Edit the tables in
`scripts/gen/` and run:

```sh
python3 scripts/gen/language.py
python3 scripts/gen/permissions.py
```

CI fails if the committed files differ from the generated ones.

## Building

```sh
./scripts/build-package.sh
```

This writes `build/packages/com.tinsever.forum.tar` and
`build/packages/com.tinsever.wcf.accountSwitcher.tar`.

## Repository layout

```
forum/             forum package (package.xml, PIP XML files)
  files/           application files (PHP, JS, SCSS)
  files_wcf/       files installed into the WCF directory
  templates/       frontend templates
  acptemplates/    ACP templates
  language/        en.xml, de.xml (generated)
account-switcher/  account switcher plugin
patches/           patches for the WoltLab importer
branding/          logo, app icon and favicons
release-notes/     notes for each release tag
scripts/           build and development helpers
docs/              documentation
vendor-ref/        WoltLab sources (not tracked, see fetch-vendor.sh)
build/, dev/       build output and local install (not tracked)
```
