{capture assign='pageTitle'}{$board->getTitle()}{if $hasThreadList && $listView->getPageNo() > 1} - {lang pageNo=$listView->getPageNo()}wcf.page.pageNo{/lang}{/if}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader forumBoardHeader">
		<div class="contentHeaderTitle">
			<h1 class="contentTitle">{$board->getTitle()}</h1>
			{if $board->description}
				<div class="contentHeaderDescription">{unsafe:$board->getFormattedDescription()}</div>
			{/if}
		</div>

		{hascontent}
			<nav class="contentHeaderNavigation">
				<ul>
					{content}
						{if $board->canStartThread()}
							<li>
								<a href="{link controller='ThreadAdd' application='forum' id=$board->boardID}{/link}" class="button buttonPrimary">
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
	{if $__wcf->user->userID && $board->isBoard()}
		<div class="contentInteractionButton dropdown jsOnly forumSubscription" data-label-default="{lang}forum.subscription.button{/lang}" data-label-watching="{lang}forum.subscription.button.watching{/lang}" data-label-ignoring="{lang}forum.subscription.button.ignoring{/lang}" data-endpoint="{link controller='Api' id='rpc'}{/link}forum/boards/{$board->boardID}/subscription" data-status="{if $board->isSubscribed()}watching{elseif $board->isIgnored()}ignoring{/if}">
			<button type="button" class="button small dropdownToggle">
				{if $board->isSubscribed()}{icon name='bookmark' type='solid'}{elseif $board->isIgnored()}{icon name='eye-slash'}{else}{icon name='bookmark'}{/if}
				<span>{if $board->isSubscribed()}{lang}forum.subscription.button.watching{/lang}{elseif $board->isIgnored()}{lang}forum.subscription.button.ignoring{/lang}{else}{lang}forum.subscription.button{/lang}{/if}</span>
			</button>
			<ul class="dropdownMenu">
				<li{if !$board->isSubscribed() && !$board->isIgnored()} class="active"{/if}><button type="button" data-status="">{lang}forum.board.subscription.normal{/lang}</button></li>
				<li{if $board->isSubscribed()} class="active"{/if}><button type="button" data-status="watching">{lang}forum.board.subscription.watching{/lang}</button></li>
				{if $board->canIgnore()}
					<li{if $board->isIgnored()} class="active"{/if}><button type="button" data-status="ignoring">{lang}forum.board.subscription.ignoring{/lang}</button></li>
				{/if}
			</ul>
		</div>
		<button type="button" class="button small contentInteractionButton jsOnly forumMarkAsReadButton" data-board-id="{$board->boardID}">
			{icon name='check'}
			<span>{lang}forum.board.markAsRead{/lang}</span>
		</button>
	{/if}
{/capture}

{capture assign='contentInteractionDropdownItems'}
	{if $board->isBoard()}
		<li><a rel="alternate" href="{link controller='ThreadFeed' application='forum' id=$board->boardID}{/link}">{lang}wcf.global.button.rss{/lang}</a></li>
	{/if}
{/capture}

{if $board->isBoard()}
	{capture append='headContent'}
		<link rel="alternate" type="application/rss+xml" title="{lang}wcf.global.button.rss{/lang}" href="{link controller='ThreadFeed' application='forum' id=$board->boardID}{/link}">
	{/capture}
{/if}

{include file='header'}

{if $board->isClosed}
	<woltlab-core-notice type="info">{lang}forum.board.isClosed{/lang}</woltlab-core-notice>
{/if}

{if !$boardNodes|empty}
	<div class="forumBoardList forumBoardList--subBoards">
		{if !$board->isCategory()}
			<h2 class="sectionTitle forumBoardList__heading">{lang}forum.board.subBoards{/lang}</h2>
		{/if}
		{include file='__boardList' application='forum'}
	</div>
{/if}

{if $announcementList !== null && $announcementList|count}
	<section class="section forumAnnouncements">
		<h2 class="sectionTitle">{lang}forum.thread.announcements{/lang}</h2>
		<div class="forumThreadList forumThreadList--static">
			{include file='threadListItems' application='forum' threads=$announcementList->getObjects() showBoard=false}
		</div>
	</section>
{/if}

{if $hasThreadList}
	<div class="section {$listView->getContainerCssClassName()}">
		{unsafe:$listView->render()}
	</div>
{elseif $boardNodes|empty}
	<woltlab-core-notice type="info">{lang}forum.board.noBoards{/lang}</woltlab-core-notice>
{/if}

<script data-relocate="true">
	require(['Forum/Ui/Board/MarkAsRead', 'Forum/Ui/Subscription'], (MarkAsRead, Subscription) => {
		MarkAsRead.setup();
		Subscription.setup();
	});
</script>

{include file='footer'}
