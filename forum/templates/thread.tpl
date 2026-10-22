{capture assign='pageTitle'}{$thread->topic}{if $pageNo > 1} - {lang}wcf.page.pageNo{/lang}{/if} - {$board->getTitle()}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader forumThreadHeader">
		<div class="contentHeaderIcon">
			{unsafe:$thread->getUserProfile()->getAvatar()->getImageTag(64)}
		</div>

		{include file='threadContentHeaderTitle' application='forum'}

		{hascontent}
			<nav class="contentHeaderNavigation">
				<ul>
					{content}
						{if $thread->canReply()}
							<li class="jsOnly">
								<button type="button" class="button buttonPrimary jsQuickReply">
									{icon name='reply'}
									<span>{lang}forum.post.add{/lang}</span>
								</button>
							</li>
						{/if}
						{event name='contentHeaderNavigation'}
					{/content}
				</ul>
			</nav>
		{/hascontent}
	</header>
{/capture}

{capture assign='contentInteractionPagination'}
	{pages print=true assign=pagesLinks application='forum' controller='Thread' object=$thread link="pageNo=%d"}
{/capture}

{capture assign='contentInteractionButtons'}
	<div class="contentInteractionButton forumThreadInteractions">
		{unsafe:$interactionContextMenu->render()}
	</div>
	{if $thread->canSubscribe()}
		<div class="contentInteractionButton dropdown jsOnly forumSubscription" data-label-default="{lang}forum.subscription.button{/lang}" data-label-watching="{lang}forum.subscription.button.watching{/lang}" data-label-ignoring="{lang}forum.subscription.button.ignoring{/lang}" data-endpoint="{link controller='Api' id='rpc'}{/link}forum/threads/{$thread->threadID}/subscription" data-status="{$threadStatus}">
			<button type="button" class="button small dropdownToggle">
				{if $threadStatus === 'watching'}{icon name='bookmark' type='solid'}{elseif $threadStatus === 'ignoring'}{icon name='eye-slash'}{else}{icon name='bookmark'}{/if}
				<span>{if $threadStatus === 'watching'}{lang}forum.subscription.button.watching{/lang}{elseif $threadStatus === 'ignoring'}{lang}forum.subscription.button.ignoring{/lang}{else}{lang}forum.subscription.button{/lang}{/if}</span>
			</button>
			<ul class="dropdownMenu">
				<li{if !$threadStatus} class="active"{/if}><button type="button" data-status="">{lang}forum.thread.subscription.normal{/lang}</button></li>
				<li{if $threadStatus === 'watching'} class="active"{/if}><button type="button" data-status="watching">{lang}forum.thread.subscription.watching{/lang}</button></li>
				<li{if $threadStatus === 'ignoring'} class="active"{/if}><button type="button" data-status="ignoring">{lang}forum.thread.subscription.ignoring{/lang}</button></li>
			</ul>
		</div>
	{/if}
	<button type="button" class="contentInteractionButton button small wsShareButton jsOnly jsTooltip" title="{lang}wcf.message.share{/lang}" data-link="{$thread->getLink()}" data-link-title="{$thread->topic}">{icon name='share-nodes'}</button>
{/capture}

{capture assign='contentInteractionDropdownItems'}
	<li><a rel="alternate" href="{link controller='ThreadFeed' application='forum' threadID=$thread->threadID}{/link}">{lang}wcf.global.button.rss{/lang}</a></li>
	{if $board->isModerator()}
		<li><a href="{link controller='ThreadLog' application='forum' id=$thread->threadID}{/link}">{lang}forum.thread.log{/lang}</a></li>
	{/if}
{/capture}

{include file='header'}

{if $thread->isDisabled}
	<woltlab-core-notice type="warning">{lang}forum.thread.isDisabled.notice{/lang}</woltlab-core-notice>
{/if}
{if $thread->isDeleted}
	<woltlab-core-notice type="error">{lang}forum.thread.isDeleted.notice{/lang}</woltlab-core-notice>
{/if}
{if $thread->isClosed && !$thread->canReply()}
	<woltlab-core-notice type="info">{lang}forum.thread.isClosed.notice{/lang}</woltlab-core-notice>
{/if}

<div class="section">
	<ul class="messageList forumPostList" id="forumPostList" data-thread-id="{$thread->threadID}">
		{include file='threadPostList' application='forum' objects=$objects showAds=true}
		{hascontent}
			<li class="messageListPagination">
				{content}{unsafe:$pagesLinks}{/content}
			</li>
		{/hascontent}
		{if $thread->canReply()}{include file='threadQuickReply' application='forum'}{/if}
	</ul>

	{if $canModeratePosts}
		<div class="listView__footer forumPostList__footer" id="forumPostList_footer" hidden>
			<div class="listView__footer__container">
				<div id="forumPostList_selectionBar" class="listView__selectionBar dropdown" hidden>
					<button type="button" id="forumPostList_bulkInteractionButton" class="button small listView__bulkInteractionButton dropdownToggle"></button>
					<ul class="dropdownMenu">
						<li class="disabled"><span>{lang}wcf.global.loading{/lang}</span></li>
						<li class="dropdownDivider"></li>
						<li>
							<button type="button" id="forumPostList_resetSelectionButton">{lang}wcf.clipboard.item.unmarkAll{/lang}</button>
						</li>
					</ul>
				</div>
			</div>
		</div>
	{/if}
</div>

{if !$tags|empty}
	<section class="section forumThreadTags">
		<h2 class="sectionTitle">{lang}wcf.tagging.tags{/lang}</h2>
		<ul class="tagList">
			{foreach from=$tags item=tag}
				<li><a href="{link controller='Tagged' object=$tag}objectType=com.tinsever.forum.thread{/link}" class="tag">{$tag->name}</a></li>
			{/foreach}
		</ul>
	</section>
{/if}

{if !$similarThreads|empty}
	<section class="section forumSimilarThreads">
		<h2 class="sectionTitle">{lang}forum.thread.similarThreads{/lang}</h2>
		<div class="forumThreadList forumThreadList--static">
			{include file='threadListItems' application='forum' threads=$similarThreads showBoard=true}
		</div>
	</section>
{/if}

<script data-relocate="true">
	{if $thread->canReply()}
		require(['WoltLabSuite/Core/Ui/Message/Reply'], (UiMessageReply) => {
			{if !$thread->getPermission('canReplyThreadWithoutModeration') && !$thread->getPermission('canEnablePost')}
				{jsphrase name='forum.post.moderation.redirect'}
			{/if}

			new UiMessageReply({
				ajax: {
					className: 'forum\\data\\post\\PostAction',
				},
				{if !$thread->getPermission('canReplyThreadWithoutModeration') && !$thread->getPermission('canEnablePost')}
					successMessage: 'forum.post.moderation.redirect',
				{/if}
			});
		});
	{/if}

	require([
		'Forum/Ui/Post/InlineEditor',
		'WoltLabSuite/Core/Component/Quote/Message',
		'Forum/Ui/Subscription',
	], ({ PostInlineEditor }, { registerContainer }, Subscription) => {
		new PostInlineEditor({$thread->threadID});
		registerContainer(".message", ".messageBody", "com.tinsever.forum.post");
		Subscription.setup();
	});

	{if MODULE_LIKE}
		require(['WoltLabSuite/Core/Ui/Reaction/Handler'], (UiReactionHandler) => {
			new UiReactionHandler('com.tinsever.forum.likeablePost', {
				canReact: {if $__wcf->getUser()->userID && $__wcf->session->getPermission('user.like.canLike')}true{else}false{/if},
				canReactToOwnContent: false,
				canViewReactions: {if LIKE_SHOW_SUMMARY}true{else}false{/if},
				containerSelector: '#forumPostList .message',
			});
		});
	{/if}
</script>

<script data-relocate="true">
	require(['Forum/Ui/Post/List'], ({ setup }) => {
		{if $canModeratePosts}
			WoltLabLanguage.registerPhrase("wcf.clipboard.button.numberOfSelectedItems", '{jslang __literal=true}wcf.clipboard.button.numberOfSelectedItems{/jslang}');
		{/if}
		setup('forumPostList'{if $canModeratePosts}, 'forum\\system\\interaction\\bulk\\user\\PostBulkInteractions', {unsafe:$postBulkDialogInteractions|json}{/if});
	});
</script>

{unsafe:$postInteractions->renderInitialization('forumPostList')}
{unsafe:$postBulkInteractionInitialization}

{include file='footer'}
