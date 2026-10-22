<div class="box48">
	{user object=$account type='avatar48' ariaHidden='true' tabindex='-1'}

	<div class="details">
		<div class="containerHeadline">
			<h3>
				{user object=$account class='username'}
				{include file='__accountSwitcherAccountBadges' application='forum'}
				{if $isActive}
					<span class="badge green">{lang}forum.accountSwitcher.active{/lang}</span>
				{/if}
			</h3>
		</div>
		<ul class="inlineList dotSeparated small">
			<li>{lang time=$linkTimes[$account->userID]}forum.accountSwitcher.linkedSince{/lang}</li>
			{if !$isActive && $account->multifactorActive}
				<li>{icon name='shield-halved' type='solid'} {lang}forum.accountSwitcher.multifactorRequired{/lang}</li>
			{/if}
			{if !$isActive && $unreadCounts[$account->userID][notifications]}
				<li>{lang count=$unreadCounts[$account->userID][notifications]}forum.accountSwitcher.unreadNotifications{/lang}</li>
			{/if}
			{if !$isActive && $unreadCounts[$account->userID][conversations]}
				<li>{lang count=$unreadCounts[$account->userID][conversations]}forum.accountSwitcher.unreadConversations{/lang}</li>
			{/if}
		</ul>

		<nav class="forumAccountSwitcherList__buttons">
			<ul class="buttonList">
				{if !$isActive && $isAvailable && !$account->banned}
					<li>
						<button type="button" class="button small forumAccountSwitcherSwitchButton" data-user-id="{$account->userID}">
							{icon name='arrow-right-arrow-left' type='solid'}
							<span>{lang}forum.accountSwitcher.switch{/lang}</span>
						</button>
					</li>
				{/if}
				<li>
					<button
						type="button"
						class="button small forumAccountSwitcherUnlinkButton"
						data-user-id="{$account->userID}"
						data-confirm-message="{if $isActive}{lang}forum.accountSwitcher.unlink.self.confirm{/lang}{else}{lang username=$account->username}forum.accountSwitcher.unlink.confirm{/lang}{/if}"
					>
						{icon name='link-slash'}
						<span>{if $isActive}{lang}forum.accountSwitcher.unlink.self{/lang}{else}{lang}forum.accountSwitcher.unlink{/lang}{/if}</span>
					</button>
				</li>
			</ul>
		</nav>
	</div>
</div>
