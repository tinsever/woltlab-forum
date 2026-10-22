{if $accounts|count}
	<ul class="forumAccountSwitcher__list" role="list">
		{foreach from=$accounts item=account}
			{assign var='__unread' value=$unreadCounts[$account->userID]}
			<li class="forumAccountSwitcher__item" data-username="{$account->username}">
				<button
					type="button"
					class="forumAccountSwitcher__switch"
					data-user-id="{$account->userID}"
					{if $account->banned}disabled title="{lang}forum.accountSwitcher.switch.error.banned{/lang}"{/if}
				>
					<span class="forumAccountSwitcher__avatar">{unsafe:$account->getAvatar()->getImageTag(48)}</span>
					<span class="forumAccountSwitcher__details">
						<span class="forumAccountSwitcher__username">{$account->username}</span>
						<span class="forumAccountSwitcher__badges">{include file='__accountSwitcherAccountBadges' application='forum'}</span>
					</span>
					<span class="forumAccountSwitcher__meta">
						{if $__unread[notifications]}
							<span class="forumAccountSwitcher__counter jsTooltip" title="{lang count=$__unread[notifications]}forum.accountSwitcher.unreadNotifications{/lang}">
								{icon name='bell' type='solid'} {#$__unread[notifications]}
							</span>
						{/if}
						{if $__unread[conversations]}
							<span class="forumAccountSwitcher__counter jsTooltip" title="{lang count=$__unread[conversations]}forum.accountSwitcher.unreadConversations{/lang}">
								{icon name='comments' type='solid'} {#$__unread[conversations]}
							</span>
						{/if}
						{if $account->multifactorActive}
							<span class="forumAccountSwitcher__multifactor jsTooltip" title="{lang}forum.accountSwitcher.multifactorRequired{/lang}">
								{icon name='shield-halved' type='solid'}
							</span>
						{/if}
					</span>
				</button>
			</li>
		{/foreach}
	</ul>
	<span class="userMenuContentStatus forumAccountSwitcher__noResults" hidden>{lang}forum.accountSwitcher.search.noResults{/lang}</span>
{else}
	<div class="userMenuContentStatus forumAccountSwitcher__empty">
		<p>{lang}forum.accountSwitcher.noAccounts{/lang}</p>
	</div>
{/if}
