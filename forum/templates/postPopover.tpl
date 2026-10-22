<div class="popover__layout">
	<div class="popover__header">
		<div class="popover__avatar">
			{user object=$post->getUserProfile() type='avatar48' ariaHidden='true' tabindex='-1'}
		</div>
		<div class="popover__title">
			{$thread->topic}
		</div>
		<div class="popover__time">
			{user object=$post->getUserProfile()} &middot; {time time=$post->time}
		</div>
	</div>

	<div class="popover__text htmlContent">
		{unsafe:$post->getExcerpt(500)}
	</div>
</div>
