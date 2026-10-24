<div class="box48">
	{user object=$account type='avatar48' ariaHidden='true' tabindex='-1'}

	<div class="details">
		<div class="containerHeadline">
			<h3>
				{user object=$account class='username'}
				{include file='__accountSwitcherAccountBadges'}
				{if $isActive}
					<span class="badge green">{lang}wcf.accountSwitcher.active{/lang}</span>
				{/if}
			</h3>
		</div>
		<ul class="inlineList dotSeparated small">
			<li>{lang time=$linkTimes[$account->userID]}wcf.accountSwitcher.linkedSince{/lang}</li>
			{if !$isActive && $account->multifactorActive}
				<li>{icon name='shield-halved' type='solid'} {lang}wcf.accountSwitcher.multifactorRequired{/lang}</li>
			{/if}
			{if !$isActive && $unreadCounts[$account->userID][notifications]}
				<li>{lang count=$unreadCounts[$account->userID][notifications]}wcf.accountSwitcher.unreadNotifications{/lang}</li>
			{/if}
			{if !$isActive && $unreadCounts[$account->userID][conversations]}
				<li>{lang count=$unreadCounts[$account->userID][conversations]}wcf.accountSwitcher.unreadConversations{/lang}</li>
			{/if}
		</ul>

		<nav class="accountSwitcherList__buttons">
			<ul class="buttonList">
				{if !$isActive && $isAvailable && !$account->banned}
					<li>
						<button type="button" class="button small accountSwitcherSwitchButton" data-user-id="{$account->userID}">
							{icon name='arrow-right-arrow-left' type='solid'}
							<span>{lang}wcf.accountSwitcher.switch{/lang}</span>
						</button>
					</li>
				{/if}
				<li>
					<button
						type="button"
						class="button small accountSwitcherUnlinkButton"
						data-user-id="{$account->userID}"
						data-confirm-message="{if $isActive}{lang}wcf.accountSwitcher.unlink.self.confirm{/lang}{else}{lang username=$account->username}wcf.accountSwitcher.unlink.confirm{/lang}{/if}"
					>
						{icon name='link-slash'}
						<span>{if $isActive}{lang}wcf.accountSwitcher.unlink.self{/lang}{else}{lang}wcf.accountSwitcher.unlink{/lang}{/if}</span>
					</button>
				</li>
			</ul>
		</nav>
	</div>
</div>
