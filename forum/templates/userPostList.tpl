{capture assign='pageTitle'}{lang user=$user}forum.post.userPosts{/lang}{/capture}

{capture assign='contentHeader'}
	<header class="contentHeader">
		<div class="contentHeaderIcon">
			{unsafe:$user->getAvatar()->getImageTag(64)}
		</div>
		<div class="contentHeaderTitle">
			<h1 class="contentTitle">{lang user=$user}forum.post.userPosts{/lang}</h1>
		</div>
	</header>
{/capture}

{include file='header'}

<div class="section">
	{unsafe:$listView->render()}
</div>

{include file='footer'}
