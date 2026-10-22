{foreach from=$view->getItems() item=post}
	<article class="listView__item forumPostListView__item" data-object-id="{$post->postID}">
		<div class="forumPostListView__item__header">
			<div class="forumPostListView__item__avatar">{unsafe:$post->getUserProfile()->getAvatar()->getImageTag(32)}</div>
			<div class="forumPostListView__item__meta">
				<h3 class="forumPostListView__item__title"><a href="{$post->getLink()}" class="forumPostLink" data-object-id="{$post->postID}">{$post->getTitle()}</a></h3>
				<small>{user object=$post->getUserProfile()} &middot; {time time=$post->time} &middot; <a href="{$post->getThread()->getBoard()->getLink()}">{$post->getThread()->getBoard()->getTitle()}</a></small>
			</div>
			<div class="forumPostListView__item__interactions">
				{unsafe:$view->renderInteractionContextMenuButton($post)}
			</div>
		</div>
		<div class="forumPostListView__item__content htmlContent">
			{unsafe:$post->getExcerpt(500)}
		</div>
	</article>
{/foreach}
