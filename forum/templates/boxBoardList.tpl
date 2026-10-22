<ol class="boxMenu forumBoardBoxList">
	{foreach from=$boxBoardNodes item=boxBoardNode}
		<li class="boxMenuDepth{$boxBoardNode->depth}{if $boxBoardNode->board->boardID == $boxActiveBoardID} active{/if}" data-board-id="{$boxBoardNode->board->boardID}">
			<a href="{$boxBoardNode->board->getLink()}" class="boxMenuLink">
				<span class="boxMenuLinkTitle">{$boxBoardNode->board->getTitle()}</span>
			</a>
		</li>
	{/foreach}
</ol>
