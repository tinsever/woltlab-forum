{if $accounts|count}
	<ul class="accountSwitcher__list" role="list">
		{foreach from=$accounts item=account}
			{assign var='__unread' value=$unreadCounts[$account->userID]}
			<li class="accountSwitcher__item" data-username="{$account->username}">
				<button
					type="button"
					class="accountSwitcher__switch"
					data-user-id="{$account->userID}"
					{if $account->banned}disabled title="{lang}wcf.accountSwitcher.switch.error.banned{/lang}"{/if}
				>
					<span class="accountSwitcher__avatar">{unsafe:$account->getAvatar()->getImageTag(48)}</span>
					<span class="accountSwitcher__details">
						<span class="accountSwitcher__username">{$account->username}</span>
						<span class="accountSwitcher__badges">{include file='__accountSwitcherAccountBadges'}</span>
					</span>
					<span class="accountSwitcher__meta">
						{if $__unread[notifications]}
							<span class="accountSwitcher__counter jsTooltip" title="{lang count=$__unread[notifications]}wcf.accountSwitcher.unreadNotifications{/lang}">
								{icon name='bell' type='solid'} {#$__unread[notifications]}
							</span>
						{/if}
						{if $__unread[conversations]}
							<span class="accountSwitcher__counter jsTooltip" title="{lang count=$__unread[conversations]}wcf.accountSwitcher.unreadConversations{/lang}">
								{icon name='comments' type='solid'} {#$__unread[conversations]}
							</span>
						{/if}
						{if $account->multifactorActive}
							<span class="accountSwitcher__multifactor jsTooltip" title="{lang}wcf.accountSwitcher.multifactorRequired{/lang}">
								{icon name='shield-halved' type='solid'}
							</span>
						{/if}
					</span>
				</button>
			</li>
		{/foreach}
	</ul>
	<span class="userMenuContentStatus accountSwitcher__noResults" hidden>{lang}wcf.accountSwitcher.search.noResults{/lang}</span>
{else}
	<div class="userMenuContentStatus accountSwitcher__empty">
		<p>{lang}wcf.accountSwitcher.noAccounts{/lang}</p>
	</div>
{/if}
