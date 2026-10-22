<li id="messageQuickReply" class="jsOnly{if $thread->userID && $thread->userID == $__wcf->getUser()->userID} messageGroupStarter{/if}{if $pageNo < $pages} messageQuickReplyCollapsed{/if}" data-object-id="{$thread->threadID}" data-last-post-time="{$thread->lastPostTime}" data-page-no="{$pageNo}">
	<article class="message messageSidebarOrientation{$__wcf->getStyleHandler()->getStyle()->getVariable('messageSidebarOrientation')|ucfirst}{if $__wcf->getUserProfileHandler()->userOnlineGroupID} userOnlineGroupMarking{$__wcf->getUserProfileHandler()->userOnlineGroupID}{/if}">
		{include file='messageSidebar' userProfile=$__wcf->getUserProfileHandler()->getUserProfile() isReply=true enableMicrodata=false}

		<div class="messageContent messageQuickReplyContent"{if $pageNo < $pages} data-placeholder="{lang}forum.post.add{/lang}"{/if}>
			<div class="messageBody">
				{if $thread->isClosed}
					<woltlab-core-notice type="warning">{lang}forum.thread.isClosed.replyNotice{/lang}</woltlab-core-notice>
				{/if}

				{event name='beforeWysiwyg'}

				<textarea
					id="text"
					name="text"
					class="wysiwygTextarea"
					data-autosave="com.tinsever.forum.postAdd-{$thread->threadID}"
					data-support-mention="true"
				></textarea>
				{include file='messageFormTabsInline' __forumQuickReplyThread=$thread}
				{if $__showPoll|isset && !$canStartPublicPoll}
					{* The poll editor expects this field even if public polls are not allowed. *}
					<input type="checkbox" id="textpollIsPublic" hidden disabled>
				{/if}
			</div>

			<footer class="messageFooter">
				<div class="formSubmit">
					<button type="submit" class="button buttonPrimary" data-type="save" accesskey="s">{lang}wcf.global.button.reply{/lang}</button>
					{include file='messageFormPreviewButton' previewMessageObjectType='com.tinsever.forum.post' previewMessageObjectID=0}
				</div>
			</footer>
		</div>
	</article>

	{include file='shared_wysiwyg'}
</li>
