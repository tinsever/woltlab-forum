<section class="section forumArticleDiscussion" id="forumArticleDiscussion">
	<header class="sectionHeader">
		<h2 class="sectionTitle">{lang}forum.article.discussion{/lang} <span class="badge">{#$discussionThread->replies}</span></h2>
	</header>

	{if $discussionPosts|count}
		{if $discussionMorePosts}
			<p class="forumArticleDiscussion__more">
				<a href="{$discussionThread->getLink()}">{lang count=$discussionMorePosts}forum.article.discussion.morePosts{/lang}</a>
			</p>
		{/if}

		<ul class="containerList forumArticleDiscussion__posts">
			{foreach from=$discussionPosts item=discussionPost}
				<li>
					<div class="box48">
						{user object=$discussionPost->getUserProfile() type='avatar48' ariaHidden='true' tabindex='-1'}

						<div>
							<div class="containerHeadline">
								<h3>
									{user object=$discussionPost->getUserProfile()}
									<small class="separatorLeft"><a href="{$discussionPost->getLink()}">{time time=$discussionPost->time}</a></small>
								</h3>
							</div>

							<div class="htmlContent">{unsafe:$discussionPost->getExcerpt(500)}</div>
						</div>
					</div>
				</li>
			{/foreach}
		</ul>
	{else}
		<p class="info" role="status">{lang}forum.article.discussion.noPosts{/lang}</p>
	{/if}

	<div class="formSubmit">
		<a href="{$discussionThread->getLink()}" class="button buttonPrimary">{icon name='comments'} <span>{lang}forum.article.discussion.goToThread{/lang}</span></a>
	</div>
</section>
