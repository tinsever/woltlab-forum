{if !$thread|isset && $container|isset}{assign var=thread value=$container}{/if}
{foreach from=$objects item=post name='forumPosts'}
	{assign var='objectID' value=$post->postID}
	{assign var='userProfile' value=$post->getUserProfile()}

	<li id="post{$post->postID}" class="anchorFixedHeader{if $thread->userID && $thread->userID == $post->userID} messageGroupStarter{/if}{if $canModeratePosts|isset && $canModeratePosts} listView__item{/if}"{if $canModeratePosts|isset && $canModeratePosts} data-object-id="{$post->postID}"{/if}>
		<article
			class="message messageSidebarOrientation{$__wcf->getStyleHandler()->getStyle()->getVariable('messageSidebarOrientation')|ucfirst} jsMessage forumPost{if $post->isDeleted} messageDeleted{/if}{if $post->isDisabled} messageDisabled{/if}{if $post->isBestAnswer()} forumPost--bestAnswer{/if}{if $userProfile->userOnlineGroupID} userOnlineGroupMarking{$userProfile->userOnlineGroupID}{/if}"
			data-user-id="{$post->userID}"
			data-can-edit="{if $post->canEdit()}true{else}false{/if}"
			data-object-id="{$post->postID}"
			{if MODULE_LIKE}{unsafe:$__wcf->getReactionHandler()->getDataAttributes('com.tinsever.forum.likeablePost', $post->postID)}{/if}
		>
			{include file='messageSidebar'}

			<div class="messageContent">
				<header class="messageHeader">
					<div class="messageHeaderBox">
						<ul class="messageHeaderMetaData">
							<li><a href="{$post->getLink()}" class="permalink messagePublicationTime">{time time=$post->time}</a></li>

							{event name='messageHeaderMetaData'}
						</ul>

						<ul class="messageStatus">
							{if $post->isBestAnswer()}<li><span class="badge label green forumPost__bestAnswerBadge">{icon name='circle-check' type='solid'} {lang}forum.post.bestAnswer{/lang}</span></li>{/if}
							{if $post->isDeleted}<li><span class="badge label red jsIconDeleted">{lang}wcf.message.status.deleted{/lang}</span></li>{/if}
							{if $post->isDisabled}<li><span class="badge label green jsIconDisabled">{lang}wcf.message.status.disabled{/lang}</span></li>{/if}
							{if $post->isClosed}<li><span class="badge label">{lang}forum.post.isClosed{/lang}</span></li>{/if}
							{if $thread->getVisitTime() < $post->time && $__wcf->user->userID && $post->userID != $__wcf->user->userID}<li><span class="badge label newMessageBadge">{lang}wcf.message.new{/lang}</span></li>{/if}

							{event name='messageStatus'}
						</ul>
					</div>

					<ul class="messageQuickOptions">
						<li><a href="{$post->getLink()}" class="wsShareButton jsTooltip" title="{lang}wcf.message.share{/lang}" data-link-title="{$post->getTitle()}">#{#$startIndex}</a></li>
						{if $canModeratePosts|isset && $canModeratePosts}
							<li class="jsOnly">
								<label class="listView__selectItem__label jsTooltip" title="{lang}wcf.clipboard.item.mark{/lang}">
									<input type="checkbox" class="listView__selectItem" aria-label="{lang}wcf.clipboard.item.mark{/lang}">
								</label>
							</li>
						{/if}
						{if $postInteractions|isset}
							{hascontent}<li class="forumPost__interactions">{content}{unsafe:$postInteractions->renderButton($post)}{/content}</li>{/hascontent}
						{/if}

						{event name='messageQuickOptions'}
					</ul>

					{event name='messageHeader'}
				</header>

				<div class="messageBody">
					{if $post->isDeleted}
						<woltlab-core-notice type="error" class="forumPost__deletedNotice">{lang}forum.post.isDeleted.notice{/lang}</woltlab-core-notice>
					{/if}

					{event name='beforeMessageText'}

					{if $post->subject}
						<h2 class="forumPost__subject">{$post->subject}</h2>
					{/if}

					{if $post->pollID && $polls|isset && $polls[$post->pollID]|isset}
						{include file='poll' poll=$polls[$post->pollID]}
					{/if}

					<div class="messageText">
						{unsafe:$post->getFormattedMessage()}
					</div>

					{event name='afterMessageText'}
				</div>

				<footer class="messageFooter">
					{include file='attachments'}

					{if $post->getUserProfile()->showSignature()}
						<div class="messageSignature">
							<div>{unsafe:$post->getUserProfile()->getSignature()}</div>
						</div>
					{/if}

					{event name='messageFooter'}

					<div class="messageFooterNotes">
						{if $post->editCount}
							<p class="messageFooterNote">{lang}forum.post.editNote{/lang}</p>
						{/if}

						{event name='messageFooterNotes'}
					</div>

					<div class="messageFooterGroup">
						{if MODULE_LIKE && $__wcf->session->getPermission('user.like.canViewLike')}
							{include file='reactionSummaryList' reactionData=$likeData objectType='com.tinsever.forum.likeablePost' objectID=$post->postID}
						{/if}

						<ul class="messageFooterButtons buttonList smallButtons jsMobileNavigation">
							{if $post->canEdit()}
								<li class="jsOnly">
									<a href="{link controller='PostEdit' application='forum' id=$post->postID}{/link}" title="{lang}forum.post.edit{/lang}" class="button jsMessageEditButton">
										{icon name='pencil'}
										<span>{lang}wcf.global.button.edit{/lang}</span>
									</a>
								</li>
							{/if}
							{if $thread->canReply()}
								<li class="jsQuoteMessage" data-object-id="{$post->postID}">
									<button type="button" title="{lang}wcf.message.quote.quoteMessage{/lang}" class="button jsTooltip">
										{icon name='quote-left' type='solid'}
										<span class="invisible">{lang}wcf.message.quote.quoteMessage{/lang}</span>
									</button>
								</li>
							{/if}
							{if $post->userID != $__wcf->getUser()->userID && $__wcf->session->getPermission('user.profile.canReportContent')}
								<li>
									<button type="button" title="{lang}wcf.moderation.report.reportContent{/lang}" class="button jsTooltip" data-report-content="com.tinsever.forum.post" data-object-id="{$post->postID}">
										{icon name='triangle-exclamation'}
										<span class="invisible">{lang}wcf.moderation.report.reportContent{/lang}</span>
									</button>
								</li>
							{/if}
							{if MODULE_LIKE && $__wcf->session->getPermission('user.like.canLike') && $post->userID != $__wcf->user->userID}
								<li>
									<button
										type="button"
										class="button jsTooltip reactButton{if $likeData[$post->postID]|isset && $likeData[$post->postID]->reactionTypeID} active{/if}"
										title="{lang}wcf.reactions.react{/lang}"
										data-reaction-type-id="{if $likeData[$post->postID]|isset && $likeData[$post->postID]->reactionTypeID}{$likeData[$post->postID]->reactionTypeID}{else}0{/if}"
									>
										{icon name='face-smile'}
										<span class="invisible">{lang}wcf.reactions.react{/lang}</span>
									</button>
								</li>
							{/if}
							{event name='messageFooterButtons'}
						</ul>
					</div>
				</footer>
			</div>
		</article>
	</li>

	{assign var="startIndex" value=$startIndex + 1}
	{if MODULE_WCF_AD && $showAds|isset && $showAds}
		{if $tpl[foreach][forumPosts][iteration] == 1}
			{hascontent}<li class="forumPostList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.thread.after1stPost')}{/content}</li>{/hascontent}
		{/if}
		{if $tpl[foreach][forumPosts][iteration] % 5 == 0}
			{hascontent}<li class="forumPostList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.thread.afterEvery5thPost')}{/content}</li>{/hascontent}
		{/if}
		{if $tpl[foreach][forumPosts][iteration] % 10 == 0}
			{hascontent}<li class="forumPostList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.thread.afterEvery10thPost')}{/content}</li>{/hascontent}
		{/if}
	{/if}
{/foreach}
