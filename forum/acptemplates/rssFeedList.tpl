{include file='header' pageTitle='forum.acp.menu.link.rssFeed.list'}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}forum.acp.menu.link.rssFeed.list{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='RssFeedAdd' application='forum'}{/link}" class="button">{icon name='plus'} <span>{lang}forum.acp.menu.link.rssFeed.add{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

<div class="section">
	{unsafe:$gridView->render()}
</div>

{include file='footer'}
