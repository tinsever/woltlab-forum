{include file='header' pageTitle='forum.acp.rssFeed.'|concat:$action}

<header class="contentHeader">
	<div class="contentHeaderTitle">
		<h1 class="contentTitle">{lang}forum.acp.rssFeed.{$action}{/lang}</h1>
	</div>

	<nav class="contentHeaderNavigation">
		<ul>
			<li><a href="{link controller='RssFeedList' application='forum'}{/link}" class="button">{icon name='list'} <span>{lang}forum.acp.menu.link.rssFeed.list{/lang}</span></a></li>
			{event name='contentHeaderNavigation'}
		</ul>
	</nav>
</header>

{unsafe:$form->getHtml()}

{include file='footer'}
