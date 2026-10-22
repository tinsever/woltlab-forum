#!/usr/bin/env bash
# Clones the WoltLab sources the build and dev scripts need into vendor-ref/.
# Override the versions with WCF_TAG / CONVERSATION_TAG.
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
DIR="$ROOT/vendor-ref"
mkdir -p "$DIR"

fetch() {
	if [ -d "$DIR/$2/.git" ]; then
		echo "vendor-ref/$2 already exists, skipping"
	else
		git clone --depth 1 --branch "$3" "https://github.com/WoltLab/$1.git" "$DIR/$2"
	fi
}

fetch WCF WCF "${WCF_TAG:-6.2.8}"
fetch com.woltlab.wcf.conversation conversation "${CONVERSATION_TAG:-6.2.7}"
