{assign var='__forumMembersSortFields' value=$view->getAvailableSortFields()}
{if $showHeader}
	<div class="forumMembersTable__header">
		<div class="forumMembersTable__user">{include file='__membersTableHeaderCell' application='forum' view=$view field='username' label='wcf.user.username'}</div>
		<div class="forumMembersTable__cell forumMembersTable__cell--registrationDate">{include file='__membersTableHeaderCell' application='forum' view=$view field='registrationDate' label='wcf.user.registrationDate'}</div>
		<div class="forumMembersTable__cell forumMembersTable__cell--posts">{include file='__membersTableHeaderCell' application='forum' view=$view field='forumPosts' label='forum.user.posts'}</div>
		{if MODULE_LIKE}
			<div class="forumMembersTable__cell forumMembersTable__cell--reactions">{include file='__membersTableHeaderCell' application='forum' view=$view field='likesReceived' label='wcf.user.reactionsReceived'}</div>
		{/if}
		<div class="forumMembersTable__cell forumMembersTable__cell--activityPoints">{include file='__membersTableHeaderCell' application='forum' view=$view field='activityPoints' label='wcf.user.activityPoint'}</div>
		<div class="forumMembersTable__cell forumMembersTable__cell--lastActivity">{include file='__membersTableHeaderCell' application='forum' view=$view field='lastActivityTime' label='wcf.user.usersOnline.lastActivity'}</div>
		<div class="forumMembersTable__interactions"></div>
	</div>
{/if}
{foreach from=$view->getItems() item='user'}
	<div class="listView__item forumMembersTable__row" data-object-id="{$user->getObjectID()}">
		<div class="forumMembersTable__user">
			<a href="{$user->getLink()}" class="forumMembersTable__avatar" aria-hidden="true" tabindex="-1">
				{unsafe:$user->getAvatar()->getImageTag(32)}
				{if $user->isOnline()}<span class="forumMembersTable__onlineIndicator jsTooltip" title="{lang username=$user->username}wcf.user.online.title{/lang}"></span>{/if}
			</a>
			<div class="forumMembersTable__name">
				<a href="{$user->getLink()}" class="userLink" data-object-id="{$user->userID}">{unsafe:$user->getFormattedUsername()}</a>
				{if $user->banned}<span class="jsTooltip jsUserBanned" title="{lang}wcf.user.banned{/lang}">{icon name='lock'}</span>{/if}
				{if MODULE_USER_RANK && $user->getUserTitle()}
					<span class="badge userTitleBadge{if $user->getRank() && $user->getRank()->cssClassName} {$user->getRank()->cssClassName}{/if}">{$user->getUserTitle()}</span>
				{/if}
			</div>
		</div>
		<div class="forumMembersTable__cell forumMembersTable__cell--registrationDate">{time time=$user->registrationDate type='plainDate'}</div>
		<div class="forumMembersTable__cell forumMembersTable__cell--posts">
			{if $user->forumPosts}
				<a href="{link controller='UserPostList' application='forum' object=$user}{/link}">{#$user->forumPosts}</a>
			{else}
				0
			{/if}
		</div>
		{if MODULE_LIKE}
			<div class="forumMembersTable__cell forumMembersTable__cell--reactions">{#$user->likesReceived}</div>
		{/if}
		<div class="forumMembersTable__cell forumMembersTable__cell--activityPoints">{#$user->activityPoints}</div>
		<div class="forumMembersTable__cell forumMembersTable__cell--lastActivity">
			{if $user->canViewOnlineStatus() && $user->getLastActivityTime()}{time time=$user->getLastActivityTime()}{/if}
		</div>
		<div class="forumMembersTable__interactions">
			{unsafe:$view->renderInteractionContextMenuButton($user)}
		</div>
	</div>
{/foreach}
