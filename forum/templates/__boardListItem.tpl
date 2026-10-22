{assign var='__board' value=$boardNode->board}
<li class="forumBoard{if $boardNode->isNew()} forumBoard--new{/if}{if $__board->isExternalLink()} forumBoard--link{/if}" data-board-id="{$__board->boardID}">
	<div class="forumBoard__icon{if $boardNode->isNew()} jsTooltip" title="{lang}forum.board.unreadThreads{/lang}{/if}">
		{if $boardNode->isNew() && !$__board->iconName}
			{icon size=32 name=$__board->getIconName() type='solid'}
		{else}
			{icon size=32 name=$__board->getIconName()}
		{/if}
	</div>

	<div class="forumBoard__main">
		<h3 class="forumBoard__title">
			<a href="{$__board->getLink()}"{if $__board->isExternalLink()} class="externalURL"{if EXTERNAL_LINK_TARGET_BLANK} target="_blank" rel="noopener"{/if}{/if}>{$__board->getTitle()}</a>
		</h3>

		{if $__board->description}
			<div class="forumBoard__description">{unsafe:$__board->getFormattedDescription()}</div>
		{/if}

		{if $boardNode->moderatorUsers|count || $boardNode->moderatorGroups|count}
			<div class="forumBoard__moderators">
				<span class="forumBoard__moderators__label">{lang}forum.board.moderators{/lang}:</span>
				<ul class="inlineList commaSeparated">
					{foreach from=$boardNode->moderatorUsers item=__moderator}
						<li>{user object=$__moderator}</li>
					{/foreach}
					{foreach from=$boardNode->moderatorGroups item=__moderatorGroup}
						<li>{$__moderatorGroup->getName()}</li>
					{/foreach}
				</ul>
			</div>
		{/if}

		<dl class="forumBoard__stats plain inlineDataList">
			{if $__board->isExternalLink()}
				<dt>{lang}forum.board.clicks{/lang}</dt>
				<dd>{$__board->clicks|shortUnit}</dd>
			{else}
				<dt>{lang}forum.board.threads{/lang}</dt>
				<dd>{$boardNode->threads|shortUnit}</dd>
				<dt>{lang}forum.board.posts{/lang}</dt>
				<dd>{$boardNode->posts|shortUnit}</dd>
			{/if}
		</dl>

		{if $boardNode->hasChildren() && $__board->showSubBoards}
			<ul class="forumBoard__subBoards">
				{foreach from=$boardNode->children item=subBoardNode}
					<li class="forumBoard__subBoard{if $subBoardNode->isNew()} forumBoard__subBoard--new{/if}">
						{if $subBoardNode->isNew() && !$subBoardNode->board->iconName}
							{icon name=$subBoardNode->board->getIconName() type='solid'}
						{else}
							{icon name=$subBoardNode->board->getIconName()}
						{/if}
						<a href="{$subBoardNode->board->getLink()}">{$subBoardNode->board->getTitle()}</a>
					</li>
				{/foreach}
			</ul>
		{/if}

		{event name='boardMain'}
	</div>

	{if !$__board->isExternalLink() && $boardNode->lastThreads|count}
		<ul class="forumBoard__lastThreads">
			{foreach from=$boardNode->lastThreads item=__lastThread}
				<li class="forumBoard__lastThread{if $__lastThread->isNew()} forumBoard__lastThread--new{/if}">
					<div class="forumBoard__lastThread__avatar">
						{user object=$__lastThread->getLastPosterProfile() type='avatar32' ariaHidden='true' tabindex='-1'}
					</div>
					<div class="forumBoard__lastThread__content">
						<div class="forumBoard__lastThread__title">
							{if $__lastThread->getLabels()|count}
								<ul class="labelList">
									{foreach from=$__lastThread->getLabels() item=__label}
										<li>{unsafe:$__label->render()}</li>
									{/foreach}
								</ul>
							{/if}
							<a href="{if $__lastThread->isNew()}{$__lastThread->getFirstNewPostLink()}{else}{$__lastThread->getLastPostLink()}{/if}" class="forumBoard__lastThread__link forumThreadLink" data-object-id="{$__lastThread->threadID}">{$__lastThread->topic}</a>
						</div>
						<div class="forumBoard__lastThread__meta">
							{if $__lastThread->lastPosterID}<a href="{$__lastThread->getLastPosterProfile()->getLink()}" class="forumBoard__lastThread__author userLink" data-object-id="{$__lastThread->lastPosterID}">{$__lastThread->lastPoster}</a>{else}<span class="forumBoard__lastThread__author">{$__lastThread->lastPoster}</span>{/if}<span class="forumBoard__lastThread__time">{time time=$__lastThread->lastPostTime}</span>
						</div>
					</div>
				</li>
			{/foreach}
		</ul>
	{/if}
</li>
