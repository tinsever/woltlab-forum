{* Consecutive boards outside of a category share one container. *}
{assign var='__boardListPlainOpen' value=false}
{foreach from=$boardNodes item=topBoardNode}
	{if $topBoardNode->board->isCategory()}
		{if $__boardListPlainOpen}
				</ol>
			</section>
			{assign var='__boardListPlainOpen' value=false}
		{/if}
		{assign var='__categoryCollapsed' value=$topBoardNode->isCollapsed()}
		<section class="forumBoardCategory{if $__categoryCollapsed} forumBoardCategory--collapsed{/if}" data-board-id="{$topBoardNode->board->boardID}">
			<header class="forumBoardCategory__header">
				<div class="forumBoardCategory__header__text">
					<h2 class="forumBoardCategory__title">
						<a href="{$topBoardNode->board->getLink()}">{$topBoardNode->board->getTitle()}</a>
					</h2>
					{if $topBoardNode->board->description}
						<div class="forumBoardCategory__description">{unsafe:$topBoardNode->board->getFormattedDescription()}</div>
					{/if}
				</div>
				{if $topBoardNode->hasChildren()}
					<button type="button" class="forumBoardCategory__toggle jsTooltip jsOnly" title="{lang}wcf.global.button.collapsible{/lang}" aria-expanded="{if $__categoryCollapsed}false{else}true{/if}" aria-controls="forumBoardCategory{$topBoardNode->board->boardID}">
						{icon name='chevron-up'}
					</button>
				{/if}
			</header>

			{if $topBoardNode->hasChildren()}
				<ol class="forumBoardList__boards" id="forumBoardCategory{$topBoardNode->board->boardID}"{if $__categoryCollapsed} hidden{/if}>
					{foreach from=$topBoardNode->children item=childBoardNode}
						{if $childBoardNode->board->isCategory()}
							{* Nested categories are compact groups, their boards are shown without the last threads. *}
							{assign var='__subCategoryCollapsed' value=$childBoardNode->isCollapsed()}
							<li class="forumBoardList__category{if $__subCategoryCollapsed} forumBoardCategory--collapsed{/if}" data-board-id="{$childBoardNode->board->boardID}">
								<div class="forumBoardList__category__header">
									<h3 class="forumBoardList__category__title"><a href="{$childBoardNode->board->getLink()}">{$childBoardNode->board->getTitle()}</a></h3>
									{if $childBoardNode->hasChildren()}
										<button type="button" class="forumBoardCategory__toggle jsTooltip jsOnly" title="{lang}wcf.global.button.collapsible{/lang}" aria-expanded="{if $__subCategoryCollapsed}false{else}true{/if}" aria-controls="forumBoardCategory{$childBoardNode->board->boardID}">
											{icon name='chevron-up'}
										</button>
									{/if}
								</div>
								{if $childBoardNode->hasChildren()}
									<ol class="forumBoardList__compactBoards" id="forumBoardCategory{$childBoardNode->board->boardID}"{if $__subCategoryCollapsed} hidden{/if}>
										{foreach from=$childBoardNode->children item=__compactBoardNode}
											{assign var='__compactBoard' value=$__compactBoardNode->board}
											<li class="forumCompactBoard{if $__compactBoardNode->isNew()} forumBoard--new{/if}" data-board-id="{$__compactBoard->boardID}">
												<span class="forumCompactBoard__icon">
													{if $__compactBoardNode->isNew() && !$__compactBoard->iconName}
														{icon size=24 name=$__compactBoard->getIconName() type='solid'}
													{else}
														{icon size=24 name=$__compactBoard->getIconName()}
													{/if}
												</span>
												<span class="forumCompactBoard__main">
													<a href="{$__compactBoard->getLink()}" class="forumCompactBoard__title{if $__compactBoard->isExternalLink()} externalURL{/if}"{if $__compactBoard->isExternalLink() && EXTERNAL_LINK_TARGET_BLANK} target="_blank" rel="noopener"{/if}>{$__compactBoard->getTitle()}</a>
													<span class="forumCompactBoard__stats">
														{if $__compactBoard->isExternalLink()}
															{lang}forum.board.clicks{/lang}: {$__compactBoard->clicks|shortUnit}
														{else}
															{lang}forum.board.threads{/lang}: {$__compactBoardNode->threads|shortUnit} · {lang}forum.board.posts{/lang}: {$__compactBoardNode->posts|shortUnit}
														{/if}
													</span>
												</span>
											</li>
										{/foreach}
									</ol>
								{/if}
							</li>
						{else}
							{include file='__boardListItem' application='forum' boardNode=$childBoardNode}
						{/if}
					{/foreach}
				</ol>
			{/if}
		</section>
	{else}
		{if !$__boardListPlainOpen}
			<section class="forumBoardCategory forumBoardCategory--plain">
				<ol class="forumBoardList__boards">
			{assign var='__boardListPlainOpen' value=true}
		{/if}
		{include file='__boardListItem' application='forum' boardNode=$topBoardNode}
	{/if}
{/foreach}
{if $__boardListPlainOpen}
		</ol>
	</section>
{/if}
