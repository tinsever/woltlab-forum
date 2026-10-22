#!/usr/bin/env bash
# Checks the PHP syntax and that all XML files are well-formed.
# Uses $PHP_BIN or php from PATH.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
PHP="${PHP_BIN:-php}"
cd "$ROOT"

echo "PHP $("$PHP" -r 'echo PHP_VERSION;')"
find forum scripts -name '*.php' -print0 \
	| xargs -0 -n 1 -P 8 "$PHP" -n -d display_errors=stderr -l >/dev/null

find forum -name '*.xml' -print0 | xargs -0 "$PHP" -r '
	libxml_use_internal_errors(true);
	$failed = false;
	foreach (array_slice($argv, 1) as $file) {
		if (!(new DOMDocument())->load($file)) {
			foreach (libxml_get_errors() as $e) {
				fprintf(STDERR, "%s:%d: %s\n", $file, $e->line, trim($e->message));
			}
			libxml_clear_errors();
			$failed = true;
		}
	}
	exit($failed ? 1 : 0);
' --
echo "lint ok"
