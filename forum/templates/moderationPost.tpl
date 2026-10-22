{if $post}
<article class="message messageReduced">
	<div class="messageContent">
		<header class="messageHeader">
			<div class="box32 messageHeaderWrapper">
				{user object=$post->getUserProfile() type='avatar32' ariaHidden='true' tabindex='-1'}

				<div class="messageHeaderBox">
					<h2 class="messageTitle"><a href="{$post->getLink()}">{$post->getTitle()}</a></h2>

					<ul class="messageHeaderMetaData">
						<li>{user object=$post->getUserProfile() class='username'}</li>
						<li><span class="messagePublicationTime">{time time=$post->time}</span></li>
						<li><a href="{$post->getThread()->getBoard()->getLink()}">{$post->getThread()->getBoard()->getTitle()}</a></li>
					</ul>
				</div>
			</div>
		</header>

		<div class="messageBody">
			<div class="messageText">
				{unsafe:$post->getFormattedMessage()}
			</div>
		</div>
	</div>
</article>
{/if}
