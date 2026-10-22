{capture assign='pageTitle'}{$__wcf->getActivePage()->getTitle()}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderTitle">
			<h1 class="contentTitle">{$__wcf->getActivePage()->getTitle()}</h1>
		</div>

		{hascontent}
			<nav class="contentHeaderNavigation">
				<ul>
					{content}
						{if $canStartThread}
							<li>
								<a href="#" class="button buttonPrimary forumThreadAddButton" data-endpoint="{link controller='ThreadAddBoardSelectDialog' application='forum'}{/link}">
									{icon name='plus'}
									<span>{lang}forum.thread.add{/lang}</span>
								</a>
							</li>
						{/if}
						{event name='contentHeaderNavigation'}
					{/content}
				</ul>
			</nav>
		{/hascontent}
	</header>
{/capture}

{capture assign='contentInteractionButtons'}
	{if $__wcf->user->userID}
		<button type="button" class="button small contentInteractionButton jsOnly forumMarkAsReadButton">
			{icon name='check'}
			<span>{lang}forum.board.markAllAsRead{/lang}</span>
		</button>
	{/if}
{/capture}

{capture assign='contentInteractionDropdownItems'}
	<li><a href="{link controller='LatestThreadList' application='forum'}{/link}">{lang}forum.thread.latestThreads{/lang}</a></li>
	<li><a href="{link controller='UnansweredThreadList' application='forum'}{/link}">{lang}forum.thread.unansweredThreads{/lang}</a></li>
	<li><a href="{link controller='UndoneThreadList' application='forum'}{/link}">{lang}forum.thread.undoneThreads{/lang}</a></li>
	{if $__wcf->user->userID}
		<li><a href="{link controller='UnreadThreadList' application='forum'}{/link}">{lang}forum.thread.unreadThreads{/lang}{if $unreadThreadCount} <span class="badge badgeUpdate">{#$unreadThreadCount}</span>{/if}</a></li>
		<li><a href="{link controller='WatchedThreadList' application='forum'}{/link}">{lang}forum.thread.watchedThreads{/lang}</a></li>
		<li><a href="{link controller='ParticipatedThreadList' application='forum'}{/link}">{lang}forum.thread.participatedThreads{/lang}</a></li>
	{/if}
	<li><a rel="alternate" href="{link controller='ThreadFeed' application='forum'}{/link}">{lang}wcf.global.button.rss{/lang}</a></li>
{/capture}

{capture append='headContent'}
	<link rel="alternate" type="application/rss+xml" title="{lang}wcf.global.button.rss{/lang}" href="{link controller='ThreadFeed' application='forum'}{/link}">
{/capture}

{include file='header'}

{if MODULE_WCF_AD}
	{hascontent}<div class="forumBoardList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.boardList.top')}{/content}</div>{/hascontent}
{/if}

{hascontent}
	<div class="forumBoardList">
		{content}{include file='__boardList' application='forum'}{/content}
	</div>
{hascontentelse}
	<woltlab-core-notice type="info">{lang}forum.board.noBoards{/lang}</woltlab-core-notice>
{/hascontent}

{if MODULE_WCF_AD}
	{hascontent}<div class="forumBoardList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.boardList.bottom')}{/content}</div>{/hascontent}
{/if}

{if FORUM_BOARD_LIST_ENABLE_STATS && $stats}
	<section class="section forumStatistics">
		<h2 class="sectionTitle">{lang}forum.board.statistics{/lang}</h2>
		<p>{lang}forum.board.statistics.summary{/lang}</p>
	</section>
{/if}

{if $usersOnlineList !== null && $usersOnlineList->stats[total]}
	<section class="section forumUsersOnline">
		<h2 class="sectionTitle"><a href="{link controller='UsersOnlineList'}{/link}">{lang}wcf.user.usersOnline{/lang}</a> <span class="badge">{#$usersOnlineList->stats[total]}</span></h2>

		<ul class="inlineList dotSeparated">
			<li>{lang}wcf.user.usersOnline.detail{/lang}</li>
			{if USERS_ONLINE_RECORD}<li>{lang}wcf.user.usersOnline.record{/lang}</li>{/if}
		</ul>

		{if $usersOnlineList|count}
			<ul class="inlineList commaSeparated forumUsersOnline__list">
				{foreach from=$usersOnlineList->getObjects() item=userOnline}
					<li>{user object=$userOnline}</li>
				{/foreach}
			</ul>
		{/if}

		{if USERS_ONLINE_ENABLE_LEGEND && $usersOnlineList->getUsersOnlineMarkings()|count}
			<dl class="plain inlineDataList usersOnlineLegend">
				<dt>{lang}wcf.user.usersOnline.marking.legend{/lang}</dt>
				<dd>
					<ul class="inlineList commaSeparated">
						{foreach from=$usersOnlineList->getUsersOnlineMarkings() item=usersOnlineMarking}
							<li>{unsafe:$usersOnlineMarking}</li>
						{/foreach}
					</ul>
				</dd>
			</dl>
		{/if}
	</section>
{/if}

<script data-relocate="true">
	require(['Forum/Ui/Board/MarkAsRead', 'Forum/Ui/Board/Collapse', 'Forum/Ui/Thread/AddButton'], (MarkAsRead, Collapse, AddButton) => {
		MarkAsRead.setup();
		Collapse.setup();
		AddButton.setup();
	});
</script>

{include file='footer'}
