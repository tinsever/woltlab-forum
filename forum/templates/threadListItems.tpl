{if !$threads|isset}{assign var='threads' value=$view->getItems()}{/if}
{foreach from=$threads item='thread' name='forumThreads'}
	<article class="listView__item forumThreadList__item{if $thread->isNew()} forumThreadList__item--new{/if}{if $thread->isDeleted} forumThreadList__item--deleted{/if}{if $thread->isDisabled} forumThreadList__item--disabled{/if}{if $thread->isAnnouncement} forumThreadList__item--announcement{elseif $thread->isSticky} forumThreadList__item--sticky{/if}" data-object-id="{$thread->getObjectID()}">
		{if !$view|empty && $view->hasBulkInteractions()}
			<div class="forumThreadList__item__select">
				<label class="listView__selectItem__label jsTooltip" title="{lang}wcf.clipboard.item.mark{/lang}">
					<input type="checkbox" class="listView__selectItem" aria-label="{lang}wcf.clipboard.item.mark{/lang}">
				</label>
			</div>
		{/if}

		<div class="forumThreadList__item__avatar">
			{unsafe:$thread->getUserProfile()->getAvatar()->getImageTag(48)}
			{if $thread->isAnnouncement}
				<span class="forumThreadList__item__badge forumThreadList__item__badge--announcement jsTooltip" title="{lang}forum.thread.isAnnouncement{/lang}">{icon name='bullhorn'}</span>
			{elseif $thread->isSticky}
				<span class="forumThreadList__item__badge forumThreadList__item__badge--sticky jsTooltip" title="{lang}forum.thread.isSticky{/lang}">{icon name='thumbtack'}</span>
			{elseif $thread->isLink()}
				<span class="forumThreadList__item__badge jsTooltip" title="{lang}forum.thread.isMoved{/lang}">{icon name='arrow-right'}</span>
			{/if}
		</div>

		<div class="forumThreadList__item__main">
			<h2 class="forumThreadList__item__title">
				{if !$view|empty && $thread->isNew()}
					{unsafe:$view->renderMarkAsReadButton($thread)}
				{/if}
				{hascontent}
					<ul class="labelList">
						{content}
							{foreach from=$thread->getLabels() item=label}
								<li>{unsafe:$label->render()}</li>
							{/foreach}
						{/content}
					</ul>
				{/hascontent}
				{if $thread->isLink()}
					<a href="{$thread->getLink()}" class="forumThreadList__item__link">{$thread->topic}</a>
				{else}
					<a href="{if $thread->isNew()}{$thread->getFirstNewPostLink()}{else}{$thread->getLink()}{/if}" class="forumThreadList__item__link forumThreadLink" data-object-id="{$thread->threadID}">{$thread->topic}</a>
				{/if}
			</h2>

			<ul class="forumThreadList__item__meta inlineList dotSeparated">
				<li>{if $thread->userID}<a href="{$thread->getUserProfile()->getLink()}" class="userLink" data-object-id="{$thread->userID}">{$thread->username}</a>{else}{$thread->username}{/if}</li>
				<li>{time time=$thread->time}</li>
				{if $showBoard|isset && $showBoard}
					<li><a href="{$thread->getBoard()->getLink()}">{$thread->getBoard()->getTitle()}</a></li>
				{/if}
				{if !$thread->isLink()}
					{assign var='__threadPages' value=$thread->getPages()}
					{if $__threadPages > 1}
						<li class="forumThreadList__item__pages">{pages print=true assign=__threadPagesLinks application='forum' controller='Thread' object=$thread link='pageNo=%d' pages=$__threadPages page=0}</li>
					{/if}
				{/if}
				{event name='meta'}
			</ul>

			{event name='content'}
		</div>

		<div class="forumThreadList__item__status">
			{if $thread->isClosed}
				<span class="jsTooltip" title="{lang}wcf.global.state.closed{/lang}">{icon name='lock'}</span>
			{/if}
			{if $thread->isDisabled}
				<span class="jsTooltip" title="{lang}wcf.message.status.disabled{/lang}">{icon name='eye-slash'}</span>
			{/if}
			{if $thread->isDeleted}
				<span class="jsTooltip" title="{lang}wcf.message.status.deleted{/lang}">{icon name='trash-can'}</span>
			{/if}
			{if !$thread->isLink() && $thread->getBoard()->enableMarkingAsDone}
				{if $thread->isDone}
					<span class="jsTooltip forumThreadList__status--done" title="{lang}forum.thread.status.done{/lang}">{icon name='circle-check' type='solid'}</span>
				{else}
					<span class="jsTooltip forumThreadList__status--undone" title="{lang}forum.thread.status.undone{/lang}">{icon name='circle-check'}</span>
				{/if}
			{/if}
			{if $thread->polls}
				<span class="jsTooltip" title="{lang}forum.thread.hasPoll{/lang}">{icon name='chart-bar'}</span>
			{/if}
			{if $thread->attachments}
				<span class="jsTooltip" title="{lang}forum.thread.hasAttachments{/lang}">{icon name='paperclip'}</span>
			{/if}
			{if $thread->isSubscribed()}
				<span class="jsTooltip" title="{lang}forum.thread.isSubscribed{/lang}">{icon name='bookmark' type='solid'}</span>
			{/if}
			{event name='status'}
		</div>

		<div class="forumThreadList__item__stats">
			{if !$thread->isLink()}
				<span class="forumThreadList__item__replies jsTooltip" title="{lang replies=$thread->replies}forum.thread.replies.count{/lang} · {lang views=$thread->views}forum.thread.views.count{/lang}">
					{icon name='comments'}
					<span>{$thread->replies|shortUnit}</span>
				</span>
			{/if}
		</div>

		<div class="forumThreadList__item__lastPost">
			{if !$thread->isLink() && $thread->lastPostTime}
				<div class="forumThreadList__item__lastPost__avatar">
					{unsafe:$thread->getLastPosterProfile()->getAvatar()->getImageTag(32)}
				</div>
				<div class="forumThreadList__item__lastPost__content">
					{if $thread->lastPosterID}
						<a href="{$thread->getLastPosterProfile()->getLink()}" class="forumThreadList__item__lastPost__author userLink" data-object-id="{$thread->lastPosterID}">{$thread->lastPoster}</a>
					{else}
						<span class="forumThreadList__item__lastPost__author">{$thread->lastPoster}</span>
					{/if}
					<a href="{$thread->getLastPostLink()}" class="forumThreadList__item__lastPost__time jsTooltip" title="{lang}forum.thread.gotoLastPost{/lang}">{time time=$thread->lastPostTime}</a>
				</div>
			{/if}
		</div>

		{if !$view|empty}
			<div class="forumThreadList__item__interactions">
				{unsafe:$view->renderInteractionContextMenuButton($thread)}
			</div>
		{/if}
	</article>
	{if MODULE_WCF_AD}
		{if $tpl[foreach][forumThreads][iteration] == 1}
			{hascontent}<div class="forumThreadList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.threadList.after1stThread')}{/content}</div>{/hascontent}
		{/if}
		{if $tpl[foreach][forumThreads][iteration] % 5 == 0}
			{hascontent}<div class="forumThreadList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.threadList.afterEvery5thThread')}{/content}</div>{/hascontent}
		{/if}
		{if $tpl[foreach][forumThreads][iteration] % 10 == 0}
			{hascontent}<div class="forumThreadList__ad">{content}{unsafe:$__wcf->getAdHandler()->getAds('com.tinsever.forum.threadList.afterEvery10thThread')}{/content}</div>{/hascontent}
		{/if}
	{/if}
{/foreach}
