"""Phrases for the RSS feed import, user conditions and other extensions."""

PHRASES = {
    # RSS feeds
    'forum.acp.menu.link.rssFeed.list': ('RSS Feeds', 'RSS-Feeds'),
    'forum.acp.menu.link.rssFeed.add': ('Add RSS Feed', 'RSS-Feed hinzufügen'),
    'forum.acp.rssFeed.add': ('Add RSS Feed', 'RSS-Feed hinzufügen'),
    'forum.acp.rssFeed.edit': ('Edit RSS Feed', 'RSS-Feed bearbeiten'),
    'forum.acp.rssFeed.url': ('Feed URL', 'Feed-URL'),
    'forum.acp.rssFeed.url.error.invalid': (
        'The feed could not be read: {$message}',
        'Der Feed konnte nicht gelesen werden: {$message}',
    ),
    'forum.acp.rssFeed.title.description': (
        'Only used in the administration, the feed URL is shown if left empty.',
        'Wird nur in der Administration verwendet, ohne Angabe wird die Feed-URL angezeigt.',
    ),
    'forum.acp.rssFeed.boardID': ('Board', 'Forum'),
    'forum.acp.rssFeed.userID': ('Author', 'Autor'),
    'forum.acp.rssFeed.userID.description': (
        'The user who is shown as the author of the imported threads.',
        'Der Benutzer, der als Autor der importierten Themen angezeigt wird.',
    ),
    'forum.acp.rssFeed.cycleTime': ('Check Interval', 'Abrufintervall'),
    'forum.acp.rssFeed.cycleTime.minutes': ('{#$minutes} minutes', '{#$minutes} Minuten'),
    'forum.acp.rssFeed.isDisabled': ('Disable feed', 'Feed deaktivieren'),
    'forum.acp.rssFeed.lastRun': ('Last Check', 'Letzter Abruf'),
    'forum.acp.rssFeed.import': ('Import now', 'Jetzt abrufen'),
    'forum.acp.rssFeed.import.settings': ('Import', 'Import'),
    'forum.acp.rssFeed.maxResults': ('Maximum Entries per Check', 'Maximale Einträge pro Abruf'),
    'forum.acp.rssFeed.maxResults.description': (
        'Limits the number of threads that are created per check, <kbd>0</kbd> disables the limit.',
        'Begrenzt die Anzahl der pro Abruf erstellten Themen, <kbd>0</kbd> deaktiviert die Begrenzung.',
    ),
    'forum.acp.rssFeed.searchKeywords': ('Keywords', 'Suchbegriffe'),
    'forum.acp.rssFeed.searchKeywords.description': (
        'Only entries containing at least one of these keywords are imported. Enter one keyword per line, leave empty to import all entries.',
        'Nur Einträge, die mindestens einen dieser Begriffe enthalten, werden importiert. Ein Begriff pro Zeile, ohne Angabe werden alle Einträge importiert.',
    ),
    'forum.acp.rssFeed.threadTags': ('Tags', 'Tags'),
    'forum.acp.rssFeed.threadTags.description': (
        'Comma-separated list of tags that are added to every imported thread.',
        'Kommagetrennte Liste von Tags, die jedem importierten Thema hinzugefügt werden.',
    ),
    'forum.acp.rssFeed.useCategoriesAsTags': (
        'Use the categories of the entries as tags',
        'Kategorien der Einträge als Tags verwenden',
    ),
    'forum.acp.rssFeed.closeThread': ('Close imported threads', 'Importierte Themen schließen'),
    'forum.acp.rssFeed.disableThread': ('Disable imported threads', 'Importierte Themen deaktivieren'),

    # user conditions and sorting
    'wcf.user.condition.forumPosts': ('Forum Posts', 'Forenbeiträge'),
    'wcf.user.sortField.forumPosts': ('Forum Posts', 'Forenbeiträge'),

    # members list as table
    'wcf.acp.option.forum_members_list_default_view': ('Default view', 'Standardansicht'),
    'wcf.acp.option.forum_members_list_default_view.cards': ('Cards', 'Karten'),
    'wcf.acp.option.forum_members_list_default_view.list': ('List', 'Liste'),
    'wcf.acp.option.forum_members_list_enable_view_switch': ('Visitors can switch the view', 'Besucher können die Ansicht wechseln'),
    'wcf.acp.option.forum_members_list_enable_view_switch.description': (
        'The chosen view is stored in a cookie.',
        'Die gewählte Ansicht wird in einem Cookie gespeichert.',
    ),
    'forum.user.members.view.cards': ('Card View', 'Kartenansicht'),
    'forum.user.members.view.list': ('List View', 'Listenansicht'),

    # article discussions
    'wcf.acp.option.category.forum.general.article': ('Article Discussions', 'Artikel-Diskussionen'),
    'wcf.acp.option.forum_article_discussion_board_id': ('Discuss articles in the forum', 'Artikel im Forum diskutieren'),
    'wcf.acp.option.forum_article_discussion_board_id.description': (
        'Articles with enabled comments are discussed in a thread of the selected board instead. The thread is created when the published article is viewed for the first time.',
        'Artikel mit aktivierten Kommentaren werden stattdessen in einem Thema des ausgewählten Forums diskutiert. Das Thema wird erstellt, sobald der veröffentlichte Artikel zum ersten Mal aufgerufen wird.',
    ),
    'forum.article.discussion': ('Discussion', 'Diskussion'),
    'forum.article.discussion.count': (
        '{if $count == 1}One reply{else}{#$count} replies{/if}',
        '{if $count == 1}Eine Antwort{else}{#$count} Antworten{/if}',
    ),
    'forum.article.discussion.message': (
        '<p>{if $articleContent->teaser}{$articleContent->teaser}{else}{$articleContent->title}{/if}</p><p><a href="{$articleContent->getLink()}">Read the full article</a></p>',
        '<p>{if $articleContent->teaser}{$articleContent->teaser}{else}{$articleContent->title}{/if}</p><p><a href="{$articleContent->getLink()}">Den vollständigen Artikel lesen</a></p>',
    ),
    'forum.article.discussion.noPosts': (
        'There are no replies yet, be the first to share your thoughts on this article.',
        'Es gibt noch keine Antworten. Teilen Sie als Erster Ihre Gedanken zu diesem Artikel.',
    ),
    'forum.article.discussion.morePosts': (
        '{if $count == 1}Show one older reply{else}Show {#$count} older replies{/if}',
        '{if $count == 1}Eine ältere Antwort anzeigen{else}{#$count} ältere Antworten anzeigen{/if}',
    ),
    'forum.article.discussion.goToThread': ('Join the discussion', 'An der Diskussion teilnehmen'),

    # data import
    'wcf.acp.dataImport.data.com.woltlab.wbb.board': ('Boards', 'Foren'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.acl': ('Board Permissions', 'Forenberechtigungen'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.thread': ('Threads', 'Themen'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.post': ('Posts', 'Beiträge'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.attachment': ('Attachments', 'Dateianhänge'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.watchedThread': ('Thread Subscriptions', 'Abonnierte Themen'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.poll': ('Polls', 'Umfragen'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.poll.option': ('Poll Options', 'Umfrageoptionen'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.poll.option.vote': ('Poll Votes', 'Umfragestimmen'),
    'wcf.acp.dataImport.data.com.woltlab.wbb.like': ('Reactions', 'Reaktionen'),

    # ad locations
    'wcf.acp.ad.location.category.com.tinsever.forum.boardList': ('Forum: Board List', 'Forum: Forenübersicht'),
    'wcf.acp.ad.location.category.com.tinsever.forum.threadList': ('Forum: Thread List', 'Forum: Themenliste'),
    'wcf.acp.ad.location.category.com.tinsever.forum.thread': ('Forum: Thread', 'Forum: Thema'),
    'wcf.acp.ad.location.com.tinsever.forum.boardList.top': ('Above the board list', 'Über der Forenübersicht'),
    'wcf.acp.ad.location.com.tinsever.forum.boardList.bottom': ('Below the board list', 'Unter der Forenübersicht'),
    'wcf.acp.ad.location.com.tinsever.forum.threadList.after1stThread': ('After the first thread', 'Nach dem ersten Thema'),
    'wcf.acp.ad.location.com.tinsever.forum.threadList.afterEvery5thThread': ('After every 5th thread', 'Nach jedem 5. Thema'),
    'wcf.acp.ad.location.com.tinsever.forum.threadList.afterEvery10thThread': ('After every 10th thread', 'Nach jedem 10. Thema'),
    'wcf.acp.ad.location.com.tinsever.forum.thread.after1stPost': ('After the first post', 'Nach dem ersten Beitrag'),
    'wcf.acp.ad.location.com.tinsever.forum.thread.afterEvery5thPost': ('After every 5th post', 'Nach jedem 5. Beitrag'),
    'wcf.acp.ad.location.com.tinsever.forum.thread.afterEvery10thPost': ('After every 10th post', 'Nach jedem 10. Beitrag'),

    'wcf.acp.option.forum_board_list_enable_online_list': ('Show users online in the board list', 'Benutzer online in der Forenübersicht anzeigen'),

    'wcf.acp.option.forum_board_list_enable_moderators': ('Show moderators in the board list', 'Moderatoren in der Forenübersicht anzeigen'),
    'forum.board.moderators': ('Moderators', 'Moderatoren'),

    # board list redesign
    'forum.thread.add.board': ('Board', 'Forum'),
    'forum.thread.add.board.description': ('Select the board in which the thread will be created.', 'Wählen Sie das Forum aus, in dem das Thema erstellt werden soll.'),
    'wcf.acp.option.forum_board_list_last_threads': ('Recent threads per board', 'Letzte Themen pro Forum'),
    'wcf.acp.option.forum_board_list_last_threads.description': ('Number of threads with the most recent activity that are shown next to each board, <kbd>0</kbd> hides them.', 'Anzahl der Themen mit der jüngsten Aktivität, die neben jedem Forum angezeigt werden, <kbd>0</kbd> blendet sie aus.'),

    'wcf.acp.box.boxController.com.tinsever.forum.hotThreadList': ('Hot Threads', 'Heiße Themen'),
}
