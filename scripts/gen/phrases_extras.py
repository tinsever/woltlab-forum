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
    # account switcher
    'forum.accountSwitcher.title': ('Switch Account', 'Account wechseln'),
    'forum.accountSwitcher.link': ('Link Account', 'Account verknüpfen'),
    'forum.accountSwitcher.manage': ('Manage Linked Accounts', 'Verknüpfte Accounts verwalten'),
    'forum.accountSwitcher.search.placeholder': ('Enter name', 'Name eingeben'),
    'forum.accountSwitcher.search.noResults': ('No accounts found.', 'Keine Accounts gefunden.'),
    'forum.accountSwitcher.noAccounts': ('You have not linked any other accounts yet.', 'Sie haben noch keine weiteren Accounts verknüpft.'),
    'forum.accountSwitcher.banned': ('Banned', 'Gesperrt'),
    'forum.accountSwitcher.active': ('Current Account', 'Aktueller Account'),
    'forum.accountSwitcher.switch': ('Switch', 'Wechseln'),
    'forum.accountSwitcher.unlink': ('Remove Link', 'Verknüpfung entfernen'),
    'forum.accountSwitcher.unlink.confirm': (
        'Do you really want to remove “{$username}” from your linked accounts?',
        'Möchten Sie „{$username}“ wirklich aus Ihren verknüpften Accounts entfernen?',
    ),
    'forum.accountSwitcher.unlink.self': ('Remove This Account', 'Diesen Account entfernen'),
    'forum.accountSwitcher.unlink.self.confirm': (
        'Do you really want to remove this account from your linked accounts? The other accounts stay linked with each other.',
        'Möchten Sie diesen Account wirklich aus Ihren verknüpften Accounts entfernen? Die übrigen Accounts bleiben miteinander verknüpft.',
    ),
    'forum.accountSwitcher.linkedSince': ('Linked: {time time=$time}', 'Verknüpft: {time time=$time}'),
    'forum.accountSwitcher.unreadNotifications': (
        '{#$count} unread notification{if $count != 1}s{/if}',
        '{#$count} ungelesene Benachrichtigung{if $count != 1}en{/if}',
    ),
    'forum.accountSwitcher.unreadConversations': (
        '{#$count} unread conversation{if $count != 1}s{/if}',
        '{#$count} ungelesene Konversation{if $count != 1}en{/if}',
    ),
    'forum.accountSwitcher.multifactorRequired': ('Requires multi-factor authentication', 'Erfordert Mehrfaktor-Authentifizierung'),
    'forum.accountSwitcher.description': (
        'Linked accounts let you switch between your accounts without logging in again.',
        'Mit verknüpften Accounts können Sie zwischen Ihren Accounts wechseln, ohne sich erneut anzumelden.',
    ),
    'forum.accountSwitcher.notAvailable': (
        'You are not allowed to use the account switcher, but you can still remove existing links.',
        'Sie dürfen den Account-Wechsler nicht verwenden, können bestehende Verknüpfungen aber weiterhin entfernen.',
    ),
    'forum.accountSwitcher.link.username.description': (
        'Username or email address of the account you want to link with this account.',
        'Benutzername oder E-Mail-Adresse des Accounts, den Sie mit diesem Account verknüpfen möchten.',
    ),
    'forum.accountSwitcher.link.success': ('The account “{$target->username}” has been linked.', 'Der Account „{$target->username}“ wurde verknüpft.'),
    'forum.accountSwitcher.link.error.self': ('You cannot link an account with itself.', 'Sie können einen Account nicht mit sich selbst verknüpfen.'),
    'forum.accountSwitcher.link.error.alreadyLinked': ('This account is already linked.', 'Dieser Account ist bereits verknüpft.'),
    'forum.accountSwitcher.link.error.banned': ('Banned accounts cannot be linked.', 'Gesperrte Accounts können nicht verknüpft werden.'),
    'forum.accountSwitcher.link.error.notPermitted': (
        'This account is not allowed to use the account switcher.',
        'Dieser Account darf den Account-Wechsler nicht verwenden.',
    ),
    'forum.accountSwitcher.link.error.limit': (
        'At most {#$limit} accounts can be linked with each other.',
        'Es können höchstens {#$limit} Accounts miteinander verknüpft werden.',
    ),
    'forum.accountSwitcher.link.error.invalidCredentials': (
        'The username or password is incorrect.',
        'Der Benutzername oder das Kennwort ist falsch.',
    ),
    'forum.accountSwitcher.link.error.tooManyFailures': (
        'There were too many failed attempts for this account, please try again later.',
        'Für diesen Account gab es zu viele fehlgeschlagene Versuche, bitte versuchen Sie es später erneut.',
    ),
    'forum.accountSwitcher.switch.error.notLinked': ('This account is not linked with your account.', 'Dieser Account ist nicht mit Ihrem Account verknüpft.'),
    'forum.accountSwitcher.switch.error.banned': ('This account is banned.', 'Dieser Account ist gesperrt.'),
    'forum.accountSwitcher.multifactor.description': (
        'Confirm the switch to this account with its multi-factor authentication. You stay logged in with your current account until then.',
        'Bestätigen Sie den Wechsel zu diesem Account mit dessen Mehrfaktor-Authentifizierung. Bis dahin bleiben Sie mit Ihrem aktuellen Account angemeldet.',
    ),
    'forum.accountSwitcher.multifactor.description.link': (
        'Confirm the link with this account with its multi-factor authentication.',
        'Bestätigen Sie die Verknüpfung mit diesem Account mit dessen Mehrfaktor-Authentifizierung.',
    ),
    'forum.accountSwitcher.multifactor.expired': (
        'The confirmation has expired, please try again.',
        'Die Bestätigung ist abgelaufen, bitte versuchen Sie es erneut.',
    ),
    'wcf.user.menu.profile.accountSwitcher': ('Linked Accounts', 'Verknüpfte Accounts'),
    'wcf.acp.option.module_forum_account_switcher': ('Account Switcher', 'Account-Wechsler'),
    'wcf.acp.option.module_forum_account_switcher.description': (
        'Users can link their accounts and switch between them without logging in again.',
        'Benutzer können ihre Accounts verknüpfen und zwischen ihnen wechseln, ohne sich erneut anzumelden.',
    ),
    'wcf.acp.option.category.forum.general.accountSwitcher': ('Account Switcher', 'Account-Wechsler'),
    'wcf.acp.option.forum_account_switcher_enable_search': ('Enable name search', 'Namenssuche aktivieren'),
    'wcf.acp.option.forum_account_switcher_enable_search.description': (
        'Shows a search field above the linked accounts in the account switcher.',
        'Zeigt im Account-Wechsler ein Suchfeld über den verknüpften Accounts an.',
    ),
    'wcf.acp.option.forum_account_switcher_max_accounts': ('Maximum linked accounts', 'Maximale Anzahl verknüpfter Accounts'),
    'wcf.acp.option.forum_account_switcher_max_accounts.description': (
        'Maximum number of accounts that can be linked with each other, <kbd>0</kbd> removes the limit.',
        'Maximale Anzahl an Accounts, die miteinander verknüpft werden können, <kbd>0</kbd> hebt die Begrenzung auf.',
    ),
    'wcf.acp.group.option.user.profile.canUseAccountSwitcher': ('Can use the account switcher', 'Kann den Account-Wechsler verwenden'),
    'wcf.acp.group.option.user.profile.canUseAccountSwitcher.description': (
        'Allows linking accounts and switching between them. Both accounts need this permission to be linked.',
        'Erlaubt das Verknüpfen von Accounts und das Wechseln zwischen ihnen. Beide Accounts benötigen diese Berechtigung, um verknüpft zu werden.',
    ),
}
