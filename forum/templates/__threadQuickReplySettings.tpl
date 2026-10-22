{if $__wcf->user->userID && $threadStatus != 'watching'}
	<dt></dt>
	<dd>
		<label><input type="checkbox" name="subscribeThread" value="1"{if FORUM_THREAD_SUBSCRIBE_DEFAULT} checked{/if}> {lang}forum.thread.subscribe{/lang}</label>
	</dd>
{/if}
{if $__forumQuickReplyThread->canClose()}
	<dt></dt>
	<dd>
		<label><input type="checkbox" name="closeThread" value="1"{if $__forumQuickReplyThread->isClosed} checked{/if}> {lang}forum.thread.close{/lang}</label>
	</dd>
{/if}
