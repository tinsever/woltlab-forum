{capture assign='wysiwygSelector'}messageEditor{$post->postID}{/capture}
<div class="messageInlineEditor">
	<textarea id="{$wysiwygSelector}" class="wysiwygTextarea"
		data-autosave="com.tinsever.forum.postEdit-{$post->postID}"
		data-support-mention="true"
	>{$text}</textarea>
	{capture assign=wysiwygContainerID}messageEditor{$post->postID}{/capture}
	{include file='messageFormTabsInline' wysiwygContainerID=$wysiwygContainerID}

	<div class="formSubmit">
		<button type="button" class="button buttonPrimary" data-type="save">{lang}wcf.global.button.save{/lang}</button>

		{include file='messageFormPreviewButton' previewMessageFieldID=$wysiwygSelector previewButtonID=$wysiwygSelector|concat:'_PreviewButton' previewMessageObjectType='com.tinsever.forum.post' previewMessageObjectID=$post->postID}

		<a href="{link controller='PostEdit' application='forum' id=$post->postID}{/link}" class="button" data-type="extended">{lang}forum.post.edit.extended{/lang}</a>
		<button type="button" class="button" data-type="cancel">{lang}wcf.global.button.cancel{/lang}</button>
	</div>

	{include file='shared_wysiwyg' wysiwygEnableUpload=true}
</div>
