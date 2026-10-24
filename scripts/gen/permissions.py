#!/usr/bin/env python3
"""Generates userGroupOption.xml and aclOption.xml of the forum from a single
permission table, so that the global group permissions and the per-board ACL
options always match (see BoardPermissionHandler)."""
import os

ROOT = os.path.join(os.path.dirname(__file__), '..', '..', 'forum')

# name, category suffix, default, guest?, user, mod, admin
USER = [
    ('canViewBoard', 'board', 1, None, None, None, None),
    ('canEnterBoard', 'board', 1, None, None, None, None),
    ('canReadThread', 'board', 1, None, None, None, None),
    ('canStartThread', 'thread', 0, None, 1, None, None),
    ('canStartThreadWithoutModeration', 'thread', 0, None, 1, None, None),
    ('canReplyThread', 'thread', 0, None, 1, None, None),
    ('canReplyOwnThread', 'thread', 0, None, 1, None, None),
    ('canReplyThreadWithoutModeration', 'thread', 0, None, 1, None, None),
    ('canSetTags', 'thread', 0, None, 1, None, None),
    ('canMarkOwnThreadAsDone', 'thread', 0, None, 1, None, None),
    ('canMarkOwnBestAnswer', 'thread', 0, None, 1, None, None),
    ('canEditOwnPost', 'post', 0, None, 1, None, None),
    ('canDeleteOwnPost', 'post', 0, None, 1, None, None),
    ('canStartPoll', 'post', 0, None, 1, None, None),
    ('canVotePoll', 'post', 0, None, 1, None, None),
    ('canUploadAttachment', 'attachment', 0, None, 1, None, None),
    ('canDownloadAttachment', 'attachment', 1, None, None, None, None),
    ('canViewAttachmentPreview', 'attachment', 1, None, None, None, None),
]
MOD = [
    ('canEditThread', 'thread'), ('canCloseThread', 'thread'), ('canPinThread', 'thread'),
    ('canStartAnnouncement', 'thread'), ('canMoveThread', 'thread'), ('canMergeThread', 'thread'),
    ('canMarkAsDoneThread', 'thread'), ('canMarkBestAnswer', 'thread'), ('canEnableThread', 'thread'),
    ('canDeleteThread', 'thread'), ('canReadDeletedThread', 'thread'), ('canRestoreThread', 'thread'),
    ('canDeleteThreadCompletely', 'thread'), ('canReadPrivateThread', 'thread'), ('canReplyClosedThread', 'thread'),
    ('canEditPost', 'post'), ('canClosePost', 'post'), ('canMovePost', 'post'), ('canMergePost', 'post'),
    ('canEnablePost', 'post'), ('canDeletePost', 'post'), ('canReadDeletedPost', 'post'),
    ('canRestorePost', 'post'), ('canDeletePostCompletely', 'post'),
]
EXTRA_USER = '''			<option name="user.forum.canStartPublicPoll">
				<categoryname>user.forum.post</categoryname>
				<optiontype>boolean</optiontype>
				<defaultvalue>0</defaultvalue>
				<userdefaultvalue>1</userdefaultvalue>
			</option>
			<option name="user.forum.postEditTimeLimit">
				<categoryname>user.forum.post</categoryname>
				<optiontype>integer</optiontype>
				<defaultvalue>0</defaultvalue>
				<minvalue>0</minvalue>
				<suffix>minutes</suffix>
			</option>
			<option name="user.forum.postDeleteTimeLimit">
				<categoryname>user.forum.post</categoryname>
				<optiontype>integer</optiontype>
				<defaultvalue>0</defaultvalue>
				<minvalue>0</minvalue>
				<suffix>minutes</suffix>
			</option>
			<option name="user.forum.maxPostLength">
				<categoryname>user.forum.post</categoryname>
				<optiontype>integer</optiontype>
				<defaultvalue>50000</defaultvalue>
				<minvalue>0</minvalue>
				<suffix>chars</suffix>
			</option>
			<option name="user.forum.floodControlTime">
				<categoryname>user.forum.post</categoryname>
				<optiontype>integer</optiontype>
				<defaultvalue>0</defaultvalue>
				<minvalue>0</minvalue>
				<suffix>seconds</suffix>
			</option>
			<option name="user.forum.maxAttachmentSize">
				<categoryname>user.forum.attachment</categoryname>
				<optiontype>uploadLimit</optiontype>
				<defaultvalue>2000000</defaultvalue>
				<minvalue>10000</minvalue>
			</option>
			<option name="user.forum.allowedAttachmentExtensions">
				<categoryname>user.forum.attachment</categoryname>
				<optiontype>lineBreakSeparatedText</optiontype>
				<defaultvalue>gif
jpg
jpeg
png
webp
bmp
zip
txt
pdf</defaultvalue>
			</option>
			<option name="user.forum.maxAttachmentCount">
				<categoryname>user.forum.attachment</categoryname>
				<optiontype>integer</optiontype>
				<defaultvalue>10</defaultvalue>
				<minvalue>1</minvalue>
				<maxvalue>100</maxvalue>
			</option>
'''


def group_option(name, category, default, user=None, mod=None, admin=None, usersonly=False):
    lines = [f'\t\t\t<option name="{name}">',
             f'\t\t\t\t<categoryname>{category}</categoryname>',
             '\t\t\t\t<optiontype>boolean</optiontype>',
             f'\t\t\t\t<defaultvalue>{default}</defaultvalue>']
    if user is not None:
        lines.append(f'\t\t\t\t<userdefaultvalue>{user}</userdefaultvalue>')
    if mod is not None:
        lines.append(f'\t\t\t\t<moddefaultvalue>{mod}</moddefaultvalue>')
    if admin is not None:
        lines.append(f'\t\t\t\t<admindefaultvalue>{admin}</admindefaultvalue>')
    if usersonly:
        lines.append('\t\t\t\t<usersonly>1</usersonly>')
    lines.append('\t\t\t</option>')
    return '\n'.join(lines)


options = []
for name, cat, default, _guest, user, mod, admin in USER:
    options.append(group_option(f'user.forum.{name}', f'user.forum.{cat}', default, user, mod, admin))
for name, cat in MOD:
    options.append(group_option(f'mod.forum.{name}', f'mod.forum.{cat}', 0, None, 1, 1, True))
options.append(group_option('admin.forum.canManageBoard', 'admin.forum', 0, None, None, 1, True))
options.append(group_option('admin.forum.canManageRssFeed', 'admin.forum', 0, None, None, 1, True))

categories = '''		<categories>
			<category name="user.forum">
				<parent>user</parent>
			</category>
			<category name="user.forum.board">
				<parent>user.forum</parent>
			</category>
			<category name="user.forum.thread">
				<parent>user.forum</parent>
			</category>
			<category name="user.forum.post">
				<parent>user.forum</parent>
			</category>
			<category name="user.forum.attachment">
				<parent>user.forum</parent>
			</category>
			<category name="mod.forum">
				<parent>mod</parent>
			</category>
			<category name="mod.forum.thread">
				<parent>mod.forum</parent>
			</category>
			<category name="mod.forum.post">
				<parent>mod.forum</parent>
			</category>
			<category name="admin.forum">
				<parent>admin</parent>
			</category>
		</categories>
'''

with open(os.path.join(ROOT, 'userGroupOption.xml'), 'w') as f:
    f.write('<?xml version="1.0" encoding="UTF-8"?>\n<data xmlns="http://www.woltlab.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/6.0/userGroupOption.xsd">\n\t<import>\n')
    f.write(categories)
    f.write('\t\t<options>\n' + '\n'.join(options) + '\n' + EXTRA_USER + '\t\t</options>\n\t</import>\n'
            # moved to the account switcher package (com.tinsever.wcf.accountSwitcher) in 1.0.0 Beta 3
            '\t<delete>\n\t\t<option name="user.profile.canUseAccountSwitcher" />\n\t</delete>\n</data>\n')

acl_cats = sorted({f'user.{c}' for _, c, *_ in USER} | {f'mod.{c}' for _, c in MOD})
with open(os.path.join(ROOT, 'aclOption.xml'), 'w') as f:
    f.write('<?xml version="1.0" encoding="UTF-8"?>\n<data xmlns="http://www.woltlab.com" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.woltlab.com http://www.woltlab.com/XSD/6.0/aclOption.xsd">\n\t<import>\n\t\t<categories>\n')
    for c in acl_cats:
        f.write(f'\t\t\t<category name="{c}">\n\t\t\t\t<objecttype>com.tinsever.forum.board</objecttype>\n\t\t\t</category>\n')
    f.write('\t\t</categories>\n\t\t<options>\n')
    for name, cat, *_ in USER:
        f.write(f'\t\t\t<option name="{name}">\n\t\t\t\t<categoryname>user.{cat}</categoryname>\n\t\t\t\t<objecttype>com.tinsever.forum.board</objecttype>\n\t\t\t</option>\n')
    for name, cat in MOD:
        f.write(f'\t\t\t<option name="{name}">\n\t\t\t\t<categoryname>mod.{cat}</categoryname>\n\t\t\t\t<objecttype>com.tinsever.forum.board</objecttype>\n\t\t\t</option>\n')
    f.write('\t\t</options>\n\t</import>\n</data>\n')

print('generated', len(options), 'group options')
