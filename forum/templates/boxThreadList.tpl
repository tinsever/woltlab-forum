<ul class="sidebarList">
	{foreach from=$boxThreadList item=boxThread}
		<li class="sidebarListItem">
			<div class="sidebarListItem__image">
				{if $boxSortField === 'lastPostTime'}
					{unsafe:$boxThread->getLastPosterProfile()->getAvatar()->getImageTag(32)}
				{else}
					{unsafe:$boxThread->getUserProfile()->getAvatar()->getImageTag(32)}
				{/if}
			</div>

			<div class="sidebarListItem__content">
				<h3 class="sidebarListItem__title">
					<a href="{if $boxSortField === 'lastPostTime' || $boxSortField === 'hot'}{$boxThread->getLastPostLink()}{else}{$boxThread->getLink()}{/if}" class="forumThreadLink sidebarListItem__link" data-object-id="{$boxThread->threadID}">{$boxThread->topic}</a>
				</h3>
			</div>

			<div class="sidebarListItem__meta">
				{if $boxSortField === 'lastPostTime'}
					<div class="sidebarListItem__meta__item">{unsafe:$boxThread->getLastPosterProfile()->getFormattedUsername()}</div>
					<div class="sidebarListItem__meta__item">{time time=$boxThread->lastPostTime}</div>
				{elseif $boxSortField === 'hot'}
					<div class="sidebarListItem__meta__item">{lang replies=$boxThread->replies}forum.thread.replies.count{/lang}</div>
					<div class="sidebarListItem__meta__item">{time time=$boxThread->lastPostTime}</div>
				{elseif $boxSortField === 'replies'}
					<div class="sidebarListItem__meta__item">{lang replies=$boxThread->replies}forum.thread.replies.count{/lang}</div>
				{elseif $boxSortField === 'views'}
					<div class="sidebarListItem__meta__item">{lang views=$boxThread->views}forum.thread.views.count{/lang}</div>
				{else}
					<div class="sidebarListItem__meta__item">{unsafe:$boxThread->getUserProfile()->getFormattedUsername()}</div>
					<div class="sidebarListItem__meta__item">{time time=$boxThread->time}</div>
				{/if}
			</div>
		</li>
	{/foreach}
</ul>
