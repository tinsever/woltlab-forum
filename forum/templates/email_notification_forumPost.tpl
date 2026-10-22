{assign var='count' value=$event->getAuthors()|count}{assign var='guestTimesTriggered' value=$event->getNotification()->guestTimesTriggered}{assign var='authors' value=$event->getAuthors()|array_values}
{if $mimeType === 'text/plain'}
{capture assign='authorList'}{lang}wcf.user.notification.mail.authorList.plaintext{/lang}{/capture}
{lang}{$languageItem}.plaintext{/lang}{if $count == 1 && !$guestTimesTriggered}

{unsafe:$post->getMailText($mimeType)}{/if} {* this line ends with a space *}
{else}
	{capture assign='authorList'}{lang}wcf.user.notification.mail.authorList.html{/lang}{/capture}
	{lang}{$languageItem}.html{/lang}
	{assign var='user' value=$event->getAuthor()}

	{if $notificationType == 'instant'}{assign var='avatarSize' value=48}
	{else}{assign var='avatarSize' value=32}{/if}
	{capture assign='messageContent'}
	<table cellpadding="0" cellspacing="0" border="0">
		<tr>
			<td><a href="{link controller='User' object=$user isHtmlEmail=true}{/link}" title="{$post->username}">{unsafe:$user->getAvatar()->getSafeImageTag($avatarSize)}</a></td>
			<td class="boxContent">
				<div class="containerHeadline">
					<h3>
						{if $post->userID}
							<a href="{link controller='User' object=$user isHtmlEmail=true}{/link}">{$post->username}</a>
						{else}
							{$post->username}
						{/if}
						&#xb7;
						<a href="{$post->getLink()}"><small>{time time=$post->time type='plainTime'}</small></a>
					</h3>
				</div>
				<div>
					{unsafe:$post->getMailText($mimeType)}
				</div>
			</td>
		</tr>
	</table>
	{/capture}
	{include file='email_paddingHelper' block=true class='box'|concat:$avatarSize content=$messageContent sandbox=true}
{/if}
