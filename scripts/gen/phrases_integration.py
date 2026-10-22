"""Phrases for the integration into WoltLab Suite Core."""

def _notification(key, title_en, title_de, msg_en, msg_de, mail_title_en, mail_title_de, plain_en, plain_de, html_en, html_de, stacked=None):
    prefix = f'forum.user.notification.post.{key}'
    phrases = {
        f'{prefix}.title': (title_en, title_de),
        f'{prefix}.message': (msg_en, msg_de),
        f'{prefix}.mail.title': (mail_title_en, mail_title_de),
        f'{prefix}.mail.plaintext': (plain_en, plain_de),
        f'{prefix}.mail.html': (html_en, html_de),
    }
    if stacked:
        phrases[f'{prefix}.title.stacked'] = stacked[0]
        phrases[f'{prefix}.message.stacked'] = stacked[1]

    return phrases


PHRASES = {
    # options
    'wcf.acp.option.category.forum': ('Forum', 'Forum'),
    'wcf.acp.option.category.forum.general': ('General', 'Allgemein'),
    'wcf.acp.option.category.forum.general.boardList': ('Board List', 'Forenübersicht'),
    'wcf.acp.option.category.forum.thread': ('Threads', 'Themen'),
    'wcf.acp.option.category.forum.thread.list': ('Thread List', 'Themenliste'),
    'wcf.acp.option.category.forum.thread.general': ('General', 'Allgemein'),
    'wcf.acp.option.category.forum.post': ('Posts', 'Beiträge'),
    'wcf.acp.option.category.forum.post.general': ('General', 'Allgemein'),
    'wcf.acp.option.forum_board_list_enable_stats': ('Show statistics in the board list', 'Statistik in der Forenübersicht anzeigen'),
    'wcf.acp.option.forum_menu_show_unread_count': ('Show the number of unread threads in the main menu', 'Anzahl ungelesener Themen im Hauptmenü anzeigen'),
    'wcf.acp.option.forum_threads_per_page': ('Threads per page', 'Themen pro Seite'),
    'wcf.acp.option.forum_thread_default_sort_field': ('Default sort field', 'Standard-Sortierfeld'),
    'wcf.acp.option.forum_thread_default_sort_order': ('Default sort order', 'Standard-Sortierreihenfolge'),
    'wcf.acp.option.forum_thread_enable_similar_threads': ('Show similar threads', 'Ähnliche Themen anzeigen'),
    'wcf.acp.option.forum_thread_similar_threads_count': ('Number of similar threads', 'Anzahl ähnlicher Themen'),
    'wcf.acp.option.forum_thread_subscribe_default': ('Watch threads by default when posting', 'Themen beim Schreiben standardmäßig abonnieren'),
    'wcf.acp.option.forum_thread_moved_link_lifetime': ('Lifetime of links to moved threads', 'Lebensdauer von Links auf verschobene Themen'),
    'wcf.acp.option.forum_thread_moved_link_lifetime.description': ('Used when moderators do not specify a lifetime, 0 keeps the links permanently.', 'Wird verwendet, wenn Moderatoren keine Lebensdauer angeben, 0 behält die Links dauerhaft.'),
    'wcf.acp.option.forum_trash_lifetime': ('Empty the trash after', 'Papierkorb leeren nach'),
    'wcf.acp.option.forum_trash_lifetime.description': ('Deleted threads and posts are permanently removed after this period, 0 disables the automatic removal.', 'Gelöschte Themen und Beiträge werden nach diesem Zeitraum endgültig entfernt, 0 deaktiviert das automatische Entfernen.'),
    'wcf.acp.option.forum_posts_per_page': ('Posts per page', 'Beiträge pro Seite'),
    'wcf.acp.option.forum_post_default_sort_order': ('Sort order of posts', 'Sortierreihenfolge der Beiträge'),
    'wcf.acp.option.forum_post_edit_grace_period': ('Grace period for edits', 'Karenzzeit für Bearbeitungen'),
    'wcf.acp.option.forum_post_edit_grace_period.description': ('Edits by the author within this period after posting are not marked as edits.', 'Bearbeitungen durch den Autor innerhalb dieses Zeitraums nach dem Verfassen werden nicht als Bearbeitung vermerkt.'),
    'wcf.acp.option.forum_post_merge_double_posts': ('Merge consecutive posts', 'Aufeinanderfolgende Beiträge zusammenführen'),
    'wcf.acp.option.forum_post_merge_double_posts.description': ('A reply is appended to the previous post of the same author if it was written within this period, 0 disables the merging.', 'Eine Antwort wird an den vorherigen Beitrag desselben Autors angehängt, wenn dieser innerhalb dieses Zeitraums verfasst wurde, 0 deaktiviert das Zusammenführen.'),

    # rebuild data
    'wcf.acp.rebuildData.forum_system_worker_PostRebuildDataWorker': ('Forum Posts', 'Forenbeiträge'),
    'wcf.acp.rebuildData.forum_system_worker_PostRebuildDataWorker.description': ('Re-parses all posts, recounts their attachments and rebuilds the activity points for forum posts.', 'Verarbeitet alle Beiträge neu, zählt ihre Dateianhänge und erneuert die Aktivitätspunkte für Forenbeiträge.'),
    'wcf.acp.rebuildData.forum_system_worker_ThreadRebuildDataWorker': ('Forum Threads', 'Forenthemen'),
    'wcf.acp.rebuildData.forum_system_worker_ThreadRebuildDataWorker.description': ('Recalculates the replies, the first and the last post of all threads.', 'Berechnet die Antworten sowie den ersten und letzten Beitrag aller Themen neu.'),
    'wcf.acp.rebuildData.forum_system_worker_BoardRebuildDataWorker': ('Boards', 'Foren'),
    'wcf.acp.rebuildData.forum_system_worker_BoardRebuildDataWorker.description': ('Recalculates the number of threads and posts and the last post of all boards.', 'Berechnet die Anzahl der Themen und Beiträge sowie den letzten Beitrag aller Foren neu.'),
    'wcf.acp.rebuildData.forum_system_worker_UserPostCounterRebuildDataWorker': ('Forum Post Counter of Users', 'Forenbeitragszähler der Benutzer'),
    'wcf.acp.rebuildData.forum_system_worker_UserPostCounterRebuildDataWorker.description': ('Recalculates the number of forum posts of all users.', 'Berechnet die Anzahl der Forenbeiträge aller Benutzer neu.'),
    'wcf.acp.rebuildData.forum_system_worker_PostSearchIndexRebuildDataWorker': ('Forum Search Index', 'Suchindex des Forums'),
    'wcf.acp.rebuildData.forum_system_worker_PostSearchIndexRebuildDataWorker.description': ('Rebuilds the search index of all forum posts.', 'Erneuert den Suchindex aller Forenbeiträge.'),

    # boxes
    'wcf.acp.box.boxController.com.tinsever.forum.threadList': ('Forum Threads', 'Forenthemen'),
    'wcf.acp.box.boxController.com.tinsever.forum.boardList': ('Forum Board List', 'Forenliste'),
    'wcf.acp.box.boxController.com.tinsever.forum.statistics': ('Forum Statistics', 'Forenstatistik'),

    # user options
    'wcf.user.option.forumThreadsPerPage': ('Threads per page', 'Themen pro Seite'),
    'wcf.user.option.forumPostsPerPage': ('Posts per page', 'Beiträge pro Seite'),

    # online locations
    'wcf.page.onlineLocation.com.tinsever.forum.Board': ('Board <a href="{$board->getLink()}">{$board->getTitle()}</a>', 'Forum <a href="{$board->getLink()}">{$board->getTitle()}</a>'),
    'wcf.page.onlineLocation.com.tinsever.forum.Thread': ('Thread <a href="{$thread->getLink()}">{$thread->topic}</a>', 'Thema <a href="{$thread->getLink()}">{$thread->topic}</a>'),

    # notifications
    'wcf.user.notification.com.tinsever.forum': ('Forum', 'Forum'),
    'wcf.user.notification.com.tinsever.forum.post.notification.post': ('New replies in watched threads', 'Neue Antworten in abonnierten Themen'),
    'wcf.user.notification.com.tinsever.forum.post.notification.thread': ('New threads in watched boards', 'Neue Themen in abonnierten Foren'),
    'wcf.user.notification.com.tinsever.forum.post.notification.mention': ('Someone mentions me in a post', 'Ich werde in einem Beitrag erwähnt'),
    'wcf.user.notification.com.tinsever.forum.post.notification.quote': ('Someone quotes my post', 'Mein Beitrag wird zitiert'),
    'wcf.user.notification.com.tinsever.forum.likeablePost.notification.like': ('Someone reacts to my post', 'Jemand reagiert auf meinen Beitrag'),
    'forum.post.like.notification.title': ('Reaction to your post', 'Reaktion auf Ihren Beitrag'),
    'forum.post.like.notification.title.stacked': ('{#$count} users reacted to your post', '{#$count} Benutzer haben auf Ihren Beitrag reagiert'),
    'forum.post.like.notification.message': ('<strong>{$author}</strong> reacted with {unsafe:$reaction->renderIcon()} to your post in the thread <strong>{$post->getThread()->topic}</strong>.', '<strong>{$author}</strong> hat mit {unsafe:$reaction->renderIcon()} auf Ihren Beitrag im Thema <strong>{$post->getThread()->topic}</strong> reagiert.'),
    'forum.post.like.notification.message.stacked': ('{unsafe:$authorList} reacted{if $reaction} with {unsafe:$reaction->renderIcon()}{/if} to your post in the thread <strong>{$post->getThread()->topic}</strong>.', '{unsafe:$authorList} haben{if $reaction} mit {unsafe:$reaction->renderIcon()}{/if} auf Ihren Beitrag im Thema <strong>{$post->getThread()->topic}</strong> reagiert.'),

    # reactions
    'wcf.like.objectType.com.tinsever.forum.likeablePost': ('Forum Posts', 'Forenbeiträge'),
    'wcf.like.title.com.tinsever.forum.likeablePost': ('Reacted with {unsafe:$reaction->render()} to a post in the thread <a href="{$post->getLink()}">{$thread->topic}</a>.', 'Hat mit {unsafe:$reaction->render()} auf einen Beitrag im Thema <a href="{$post->getLink()}">{$thread->topic}</a> reagiert.'),

    # recent activity / activity points
    'wcf.user.recentActivity.com.tinsever.forum.recentActivityEvent.thread': ('Forum Thread', 'Forenthema'),
    'wcf.user.recentActivity.com.tinsever.forum.recentActivityEvent.post': ('Forum Post', 'Forenbeitrag'),
    'wcf.user.recentActivity.com.tinsever.forum.likeablePost.recentActivityEvent': ('Reaction to a Forum Post', 'Reaktion auf Forenbeitrag'),
    'wcf.user.activityPoint.objectType.com.tinsever.forum.activityPointEvent.post': ('Forum Posts', 'Forenbeiträge'),

    # search, tagging, labels
    'wcf.search.type.com.tinsever.forum.post': ('Forum', 'Forum'),
    'wcf.search.object.com.tinsever.forum.post': ('Forum Post', 'Forenbeitrag'),
    'wcf.tagging.objectType.com.tinsever.forum.thread': ('Threads', 'Themen'),
    'wcf.tagging.combinedTaggedObjects.com.tinsever.forum.thread': ('Threads', 'Themen'),
    'wcf.tagging.taggedObjects.com.tinsever.forum.thread': ('Threads tagged with “{$tag->name}”', 'Themen mit dem Tag „{$tag->name}“'),
    'wcf.acp.label.container.com.tinsever.forum.board': ('Boards', 'Foren'),

    # moderation, statistics, sitemaps, content providers
    'wcf.moderation.type.com.tinsever.forum.thread': ('Thread', 'Thema'),
    'wcf.moderation.type.com.tinsever.forum.post': ('Post', 'Beitrag'),
    'wcf.acp.stat.category.com.tinsever.forum': ('Forum', 'Forum'),
    'wcf.acp.stat.com.tinsever.forum.thread': ('Threads', 'Themen'),
    'wcf.acp.stat.com.tinsever.forum.post': ('Posts', 'Beiträge'),
    'wcf.acp.sitemap.objectType.com.tinsever.forum.board': ('Boards', 'Foren'),
    'wcf.acp.sitemap.objectType.com.tinsever.forum.thread': ('Threads', 'Themen'),
    'wcf.acp.content.provider.com.tinsever.forum.thread': ('Threads', 'Themen'),
    'wcf.acp.content.provider.com.tinsever.forum.post': ('Posts', 'Beiträge'),
}

PHRASES.update(_notification(
    'post',
    'New Reply', 'Neue Antwort',
    '<strong>{$author}</strong> replied to the thread <strong>{$thread->topic}</strong>.',
    '<strong>{$author}</strong> hat auf das Thema <strong>{$thread->topic}</strong> geantwortet.',
    '"{unsafe:$author}" replied to "{unsafe:$thread->topic}"', '„{unsafe:$author}“ hat auf „{unsafe:$thread->topic}“ geantwortet',
    '{unsafe:$authorList} replied to the thread “{unsafe:$thread->topic}” [URL:{unsafe:$post->getLink()}]{if $count == 1 && !$guestTimesTriggered}:{else}.{/if}',
    '{unsafe:$authorList} {if $count == 1}hat{else}haben{/if} auf das Thema „{unsafe:$thread->topic}“ geantwortet [URL:{unsafe:$post->getLink()}]{if $count == 1 && !$guestTimesTriggered}:{else}.{/if}',
    '<p>{unsafe:$authorList} replied to the thread <a href="{$post->getLink()}">{$thread->topic}</a>:</p>',
    '<p>{unsafe:$authorList} {if $count == 1}hat{else}haben{/if} auf das Thema <a href="{$post->getLink()}">{$thread->topic}</a> geantwortet:</p>',
    stacked=(
        ('{#$count} new replies', '{#$count} neue Antworten'),
        ('{unsafe:$authors[0]->username|encodeJS} and {#$others} {if $others == 1}other{else}others{/if} replied to the thread <strong>{$thread->topic}</strong>.'.replace('|encodeJS', ''),
         '<strong>{$author}</strong> und {#$others} {if $others == 1}weiterer{else}weitere{/if} haben auf das Thema <strong>{$thread->topic}</strong> geantwortet.'),
    ),
))
PHRASES['forum.user.notification.post.post.message.stacked'] = (
    '<strong>{$author}</strong> and {#$others} {if $others == 1}other{else}others{/if} replied to the thread <strong>{$thread->topic}</strong>.',
    '<strong>{$author}</strong> und {#$others} {if $others == 1}weiterer{else}weitere{/if} haben auf das Thema <strong>{$thread->topic}</strong> geantwortet.',
)
PHRASES.update(_notification(
    'thread',
    'New Thread', 'Neues Thema',
    '<strong>{$author}</strong> started the thread <strong>{$thread->topic}</strong> in <strong>{$thread->getBoard()->getTitle()}</strong>.',
    '<strong>{$author}</strong> hat das Thema <strong>{$thread->topic}</strong> in <strong>{$thread->getBoard()->getTitle()}</strong> gestartet.',
    'New thread "{unsafe:$thread->topic}"', 'Neues Thema „{unsafe:$thread->topic}“',
    '{unsafe:$authorList} started the thread “{unsafe:$thread->topic}” [URL:{unsafe:$post->getLink()}]:',
    '{unsafe:$authorList} hat das Thema „{unsafe:$thread->topic}“ gestartet [URL:{unsafe:$post->getLink()}]:',
    '<p>{unsafe:$authorList} started the thread <a href="{$post->getLink()}">{$thread->topic}</a>:</p>',
    '<p>{unsafe:$authorList} hat das Thema <a href="{$post->getLink()}">{$thread->topic}</a> gestartet:</p>',
))
PHRASES.update(_notification(
    'mention',
    'Mentioned in a Post', 'In einem Beitrag erwähnt',
    '<strong>{$author}</strong> mentioned you in the thread <strong>{$thread->topic}</strong>.',
    '<strong>{$author}</strong> hat Sie im Thema <strong>{$thread->topic}</strong> erwähnt.',
    '"{unsafe:$author}" mentioned you in "{unsafe:$thread->topic}"', '„{unsafe:$author}“ hat Sie in „{unsafe:$thread->topic}“ erwähnt',
    '{unsafe:$authorList} mentioned you in the thread “{unsafe:$thread->topic}” [URL:{unsafe:$post->getLink()}]:',
    '{unsafe:$authorList} hat Sie im Thema „{unsafe:$thread->topic}“ erwähnt [URL:{unsafe:$post->getLink()}]:',
    '<p>{unsafe:$authorList} mentioned you in the thread <a href="{$post->getLink()}">{$thread->topic}</a>:</p>',
    '<p>{unsafe:$authorList} hat Sie im Thema <a href="{$post->getLink()}">{$thread->topic}</a> erwähnt:</p>',
))
PHRASES.update(_notification(
    'quote',
    'Your Post was Quoted', 'Ihr Beitrag wurde zitiert',
    '<strong>{$author}</strong> quoted your post in the thread <strong>{$thread->topic}</strong>.',
    '<strong>{$author}</strong> hat Ihren Beitrag im Thema <strong>{$thread->topic}</strong> zitiert.',
    '"{unsafe:$author}" quoted you in "{unsafe:$thread->topic}"', '„{unsafe:$author}“ hat Sie in „{unsafe:$thread->topic}“ zitiert',
    '{unsafe:$authorList} quoted your post in the thread “{unsafe:$thread->topic}” [URL:{unsafe:$post->getLink()}]:',
    '{unsafe:$authorList} hat Ihren Beitrag im Thema „{unsafe:$thread->topic}“ zitiert [URL:{unsafe:$post->getLink()}]:',
    '<p>{unsafe:$authorList} quoted your post in the thread <a href="{$post->getLink()}">{$thread->topic}</a>:</p>',
    '<p>{unsafe:$authorList} hat Ihren Beitrag im Thema <a href="{$post->getLink()}">{$thread->topic}</a> zitiert:</p>',
))

# labels in the ACP's global modification log
_THREAD_LOG_ACTIONS = {
    'close': ('Closed', 'Geschlossen'), 'open': ('Opened', 'Geöffnet'),
    'sticky': ('Pinned', 'Angeheftet'), 'unsticky': ('Unpinned', 'Nicht mehr angeheftet'),
    'announcement': ('Turned into an announcement', 'Als Ankündigung festgelegt'),
    'unannouncement': ('Announcement removed', 'Ankündigung entfernt'),
    'move': ('Moved', 'Verschoben'), 'trash': ('Moved to the trash', 'In den Papierkorb verschoben'),
    'restore': ('Restored', 'Wiederhergestellt'), 'delete': ('Deleted', 'Gelöscht'),
    'enable': ('Approved', 'Freigeschaltet'), 'disable': ('Disabled', 'Deaktiviert'),
    'merge': ('Merged', 'Zusammengeführt'), 'edit': ('Topic changed', 'Titel geändert'),
    'done': ('Marked as done', 'Als erledigt markiert'), 'undone': ('Marked as not done', 'Als unerledigt markiert'),
    'bestAnswer': ('Best answer changed', 'Beste Antwort geändert'),
}
_POST_LOG_ACTIONS = {
    'trash': ('Moved to the trash', 'In den Papierkorb verschoben'), 'restore': ('Restored', 'Wiederhergestellt'),
    'delete': ('Deleted', 'Gelöscht'), 'enable': ('Approved', 'Freigeschaltet'),
    'disable': ('Disabled', 'Deaktiviert'), 'close': ('Locked', 'Gesperrt'), 'open': ('Unlocked', 'Entsperrt'),
    'edit': ('Edited', 'Bearbeitet'), 'move': ('Moved', 'Verschoben'), 'copy': ('Copied', 'Kopiert'),
    'merge': ('Merged', 'Zusammengeführt'),
}
for _action, (_en, _de) in _THREAD_LOG_ACTIONS.items():
    PHRASES[f'wcf.acp.modificationLog.com.tinsever.forum.thread.{_action}'] = (f'Forum Thread: {_en}', f'Forenthema: {_de}')
for _action, (_en, _de) in _POST_LOG_ACTIONS.items():
    PHRASES[f'wcf.acp.modificationLog.com.tinsever.forum.post.{_action}'] = (f'Forum Post: {_en}', f'Forenbeitrag: {_de}')
