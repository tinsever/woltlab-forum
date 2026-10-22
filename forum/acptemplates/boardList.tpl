{include file='header' pageTitle='forum.acp.board.list'}

{if $boardNodes|count}
	<script data-relocate="true">
		require(['WoltLabSuite/Core/Ui/Sortable/List'], function (UiSortableList) {
			new UiSortableList({
				containerId: 'boardList',
				className: 'forum\\data\\board\\BoardAction',
			});
		});
	</script>
{/if}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}forum.acp.board.list{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='BoardAdd' application='forum'}{/link}" class="button">{icon name='plus'} <span>{lang}forum.acp.board.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{if $boardNodes|count}
	<div id="boardList" class="section sortableListContainer">
		<ol class="sortableList jsObjectActionContainer" data-object-action-class-name="forum\data\board\BoardAction" data-object-id="0">
			{assign var=oldDepth value=0}
			{foreach from=$boardNodes item='boardNode'}
				{section name=i loop=$oldDepth-$boardNode->depth}</ol></li>{/section}

				<li class="sortableNode jsObjectActionObject" data-object-id="{$boardNode->board->boardID}">
					<span class="sortableNodeLabel">
						<span class="title">
							{icon name=$boardNode->board->getIconName()}
							<a href="{link controller='BoardEdit' application='forum' id=$boardNode->board->boardID}{/link}">{$boardNode->board->getTitle()}</a>
							<small>({lang}forum.acp.board.boardType.{if $boardNode->board->isCategory()}category{elseif $boardNode->board->isExternalLink()}link{else}board{/if}{/lang})</small>
						</span>

						<span class="statusDisplay buttons">
							<span class="sortableNodeHandle">
								{icon name='arrows-up-down-left-right'}
							</span>
							<a href="{link controller='BoardEdit' application='forum' id=$boardNode->board->boardID}{/link}" title="{lang}wcf.global.button.edit{/lang}" class="jsTooltip">{icon name='pencil'}</a>
							<button
								type="button"
								class="jsObjectAction jsTooltip"
								title="{lang}wcf.global.button.delete{/lang}"
								data-object-action="delete"
								data-confirm-message="{lang __encode=true title=$boardNode->board->getTitle()}forum.acp.board.delete.sure{/lang}"
							>
								{icon name='xmark'}
							</button>

							{event name='itemButtons'}
						</span>
					</span>

					<ol class="sortableList jsObjectActionObjectChildren" data-object-id="{$boardNode->board->boardID}">{if !$boardNode->hasChildren()}</ol></li>{/if}
				{assign var=oldDepth value=$boardNode->depth}
			{/foreach}
			{section name=i loop=$oldDepth}</ol></li>{/section}
		</ol>
	</div>

	<div class="formSubmit">
		<button type="button" class="button buttonPrimary" data-type="submit">{lang}wcf.global.button.saveSorting{/lang}</button>
	</div>
{else}
	<woltlab-core-notice type="info">{lang}forum.board.noBoards{/lang}</woltlab-core-notice>
{/if}

{include file='footer'}
