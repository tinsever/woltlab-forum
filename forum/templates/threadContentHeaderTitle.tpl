<div class="contentHeaderTitle">
	<h1 class="contentTitle">
		{if $thread->isAnnouncement}<span class="jsTooltip forumThreadTitle__status" title="{lang}forum.thread.isAnnouncement{/lang}">{icon name='bullhorn'}</span>{elseif $thread->isSticky}<span class="jsTooltip forumThreadTitle__status" title="{lang}forum.thread.isSticky{/lang}">{icon name='thumbtack'}</span>{/if}
		{$thread->topic}
	</h1>

	<ul class="inlineList contentHeaderMetaData">
		{hascontent}
			<li>
				{icon name='tags'}
				<ul class="labelList">
					{content}
						{foreach from=$thread->getLabels() item=label}
							<li>{unsafe:$label->render()}</li>
						{/foreach}
					{/content}
				</ul>
			</li>
		{/hascontent}

		<li>
			{icon name='user'}
			{user object=$thread->getUserProfile()}
		</li>

		<li>
			{icon name='clock'}
			<a href="{$thread->getLink()}">{time time=$thread->time}</a>
		</li>

		<li>
			{icon name='folder'}
			<a href="{$thread->getBoard()->getLink()}">{$thread->getBoard()->getTitle()}</a>
		</li>

		{if $thread->isClosed}
			<li>
				{icon name='lock'}
				{lang}wcf.global.state.closed{/lang}
			</li>
		{/if}

		{if $thread->getBoard()->enableMarkingAsDone}
			<li class="forumThreadTitle__done{if $thread->isDone} forumThreadTitle__done--done{/if}">
				{if $thread->isDone}{icon name='circle-check' type='solid'} {lang}forum.thread.status.done{/lang}{else}{icon name='circle-check'} {lang}forum.thread.status.undone{/lang}{/if}
			</li>
		{/if}

		{event name='contentHeaderMetaData'}
	</ul>
</div>
