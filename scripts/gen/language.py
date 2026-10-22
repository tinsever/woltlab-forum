#!/usr/bin/env python3
"""Generates forum/language/en.xml and forum/language/de.xml from the phrase
tables below. Permission labels are shared between user group options and
board ACL options."""
import os
import sys

sys.path.insert(0, os.path.dirname(__file__))
from phrases import PHRASES, PERMISSIONS, PERMISSION_CATEGORIES  # noqa: E402

ROOT = os.path.join(os.path.dirname(__file__), '..', '..', 'forum')

CATEGORY_PREFIXES = [
    'wcf.acp.group', 'wcf.acp.option', 'wcf.acp.stat', 'wcf.acp.sitemap', 'wcf.acp.content',
    'wcf.acp.rebuildData', 'wcf.acp.label', 'wcf.acl', 'wcf.user.option', 'wcf.user.notification',
    'wcf.user.recentActivity', 'wcf.user.activityPoint', 'wcf.page', 'wcf.like', 'wcf.search',
    'wcf.tagging', 'wcf.moderation', 'wcf.acp.box', 'wcf.user', 'wcf.acp.dataImport', 'wcf.acp.ad',
]


def category(name):
    if name.startswith('forum.'):
        parts = name.split('.')
        return '.'.join(parts[:2]) if len(parts) > 2 else 'forum.global'
    for prefix in CATEGORY_PREFIXES:
        if name == prefix or name.startswith(prefix + '.'):
            return prefix
    return '.'.join(name.split('.')[:2])


def collect():
    phrases = dict(PHRASES)
    for perm, (scope, en, de, desc_en, desc_de) in PERMISSIONS.items():
        group_name = f'{scope}.forum.{perm}'
        phrases[f'wcf.acp.group.option.{group_name}'] = (en, de)
        if desc_en:
            phrases[f'wcf.acp.group.option.{group_name}.description'] = (desc_en, desc_de)
        if perm.startswith('can'):
            phrases[f'wcf.acl.option.com.tinsever.forum.board.{perm}'] = (en, de)
    for cat, (en, de) in PERMISSION_CATEGORIES.items():
        phrases[f'wcf.acp.group.option.category.{cat}'] = (en, de)
        if cat.count('.') == 2:
            scope, _forum, sub = cat.split('.')
            phrases[f'wcf.acl.option.category.com.tinsever.forum.board.{scope}.{sub}'] = (en, de)
    return phrases


def write(language_code, index):
    phrases = collect()
    grouped = {}
    for name in sorted(phrases):
        grouped.setdefault(category(name), []).append(name)

    lines = [
        '<?xml version="1.0" encoding="UTF-8"?>',
        f'<language xmlns="http://www.woltlab.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
        f'xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/6.0/language.xsd" '
        f'languagecode="{language_code}">',
    ]
    for cat in sorted(grouped):
        lines.append(f'\t<category name="{cat}">')
        for name in grouped[cat]:
            value = phrases[name][index]
            if ']]>' in value:
                raise ValueError(name)
            lines.append(f'\t\t<item name="{name}"><![CDATA[{value}]]></item>')
        lines.append('\t</category>')
    lines.append('</language>')

    os.makedirs(os.path.join(ROOT, 'language'), exist_ok=True)
    with open(os.path.join(ROOT, 'language', f'{language_code}.xml'), 'w') as f:
        f.write('\n'.join(lines) + '\n')

    return len(phrases)


count = write('en', 0)
write('de', 1)
print(f'generated {count} phrases')
