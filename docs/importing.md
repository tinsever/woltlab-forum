# Importing an existing forum

Plenum imports from WoltLab Burning Board through the WoltLab importer. Use
Plenum 1.0.0 Beta 2 or newer together with the patched importer below.

## Before you start

- Back up both systems.
- After a failed attempt, restore the target to its pre-import state before you
  start again. Boards and posts that were already imported are not deduplicated.

## Running the import

1. Install the forum package and the importer
   (`com.woltlab.wcf.exporter.tar`, attached to each release) through the ACP
   package upload.
2. In the data-import form, enter the old database credentials and its Core
   table prefix.
3. The installation path must point to the old Core directory that contains
   `lib/core.functions.php`, `attachments/` and `images/`, including a Core
   subdirectory if the old forum uses one. Keep these files available until
   the attachment and avatar imports have finished.
4. Afterwards, run these data-rebuild jobs in order: reactions, forum posts,
   forum threads, boards, user forum-post counters, forum search index.
5. Check the imported content and access permissions before you open the site.

## Images

WoltLab Suite 6.2 converts JPEG and PNG images to WebP by default
(Configuration > Options > General > System > Images, "Convert images").
Imported avatars and attachments are converted when the rebuild job "Files"
runs. Nothing is lost and the attachment IDs stay the same. Set the option to
"Keep format" before importing to keep the original formats. GIFs are never
converted.

Attachment uploads are limited by the allowed file extensions of the user groups
(user group options > Forum > Attachments). Check them after importing the
groups.

## The patched importer

```sh
./scripts/build-importer.sh
```

This fetches the official LGPL importer 6.2.7 and builds
`build/packages/com.woltlab.wcf.exporter.tar` (local version 6.2.8 dev 3). It
needs Core 6.2.7 or newer within 6.2. The patch:

- fixes label imports and keeps label settings
- keeps silent subscriptions
- imports reaction types and fixes group translations
- keeps attachment IDs from shifting when the target already has attachments,
  so embedded images keep pointing at the right attachment

The build also produces `WBB4xExporter.class.php`. Copy it over
`lib/system/exporter/` in the target Core to resume an interrupted import.
