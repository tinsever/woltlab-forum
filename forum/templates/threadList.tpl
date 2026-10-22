{capture assign='pageTitle'}{if $user|isset}{lang user=$user}forum.thread.userThreads{/lang}{else}{$__wcf->getActivePage()->getTitle()}{/if}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderTitle">
			<h1 class="contentTitle">{$pageTitle}</h1>
		</div>
	</header>
{/capture}

{if $threadListFilter === 'unread'}
	{capture assign='contentInteractionButtons'}
		<button type="button" class="button small contentInteractionButton jsOnly forumMarkAsReadButton">
			{icon name='check'}
			<span>{lang}forum.board.markAllAsRead{/lang}</span>
		</button>
	{/capture}
{/if}

{include file='header'}

<div class="section {$listView->getContainerCssClassName()}">
	{unsafe:$listView->render()}
</div>

<script data-relocate="true">
	require(['Forum/Ui/Board/MarkAsRead'], ({ setup }) => {
		setup();
	});
</script>

{include file='footer'}
