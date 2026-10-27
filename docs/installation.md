# Installation and updates

## Requirements

- WoltLab Suite Core 6.2.x (6.3 is not supported yet)
- PHP and database as required by Core 6.2 (PHP 8.1.2 to 8.4.x)

## Through the package server

Recommended, because updates then show up in the ACP.

1. In the ACP, go to Configuration > Packages > Package Servers and add
   `https://woltlab-pkgs.tin.codes/`.
2. Search for "Plenum" under Install Package. Releases up to 1.0.0 Beta 4 are
   listed as "Forum".

The package server mirrors the GitHub releases every ten minutes. It does not
serve the patched importer (`com.woltlab.*`), because WoltLab Suite ignores such
packages from other servers. Download it from the release instead.

## By upload

1. Download `com.tinsever.forum.tar` from the
   [releases](https://github.com/tinsever/woltlab-forum/releases), or
   [build it yourself](development.md#building).
2. In the ACP, go to Configuration > Packages > Install Package and upload the
   archive.

## After installing

Create your boards under Forum > Boards. The forum settings are under
Configuration > Options > Forum.

The footer shows "Forum Software: Plenum" next to the WoltLab Suite notice. Turn
it off under Configuration > Options > Forum > General > Footer.

## Updating

With the package server, WoltLab Suite lists the update in the ACP. Otherwise
upload the newer archive. All 1.0.0 Alpha and Beta versions can update to the
latest release.
