{if $__wcf->getAccountSwitcherHandler()->isAvailable()}
	{assign var='__forumAccountSwitcherUnread' value=$__wcf->getAccountSwitcherHandler()->getUnreadCountOfOtherAccounts()}
	<li id="forumAccountSwitcher" data-count="{$__forumAccountSwitcherUnread}">
		<a
			class="jsTooltip"
			href="{link controller='AccountSwitcher' application='forum'}{/link}"
			title="{lang}forum.accountSwitcher.title{/lang}"
			role="button"
			tabindex="0"
			aria-haspopup="true"
			aria-expanded="false"
		>
			{icon size=32 name='arrow-right-arrow-left' type='solid'}
			<span>{lang}forum.accountSwitcher.title{/lang}</span>
			{if $__forumAccountSwitcherUnread}
				<span class="badge badgeUpdate">{#$__forumAccountSwitcherUnread}</span>
			{/if}
		</a>
		<script data-relocate="true">
			require(["Forum/Ui/User/Menu/AccountSwitcher"], ({ setup }) => {
				WoltLabLanguage.registerPhrase('forum.accountSwitcher.title', '{jslang}forum.accountSwitcher.title{/jslang}');
				WoltLabLanguage.registerPhrase('forum.accountSwitcher.link', '{jslang}forum.accountSwitcher.link{/jslang}');
				WoltLabLanguage.registerPhrase('forum.accountSwitcher.manage', '{jslang}forum.accountSwitcher.manage{/jslang}');
				WoltLabLanguage.registerPhrase('forum.accountSwitcher.search.placeholder', '{jslang}forum.accountSwitcher.search.placeholder{/jslang}');
				setup({
					manageLink: '{link controller='AccountSwitcher' application='forum' encode=false}{/link}',
					linkLink: '{link controller='AccountSwitcher' application='forum' encode=false}link=1{/link}',
					enableSearch: {if FORUM_ACCOUNT_SWITCHER_ENABLE_SEARCH}true{else}false{/if},
				});
			});
		</script>
	</li>
{/if}
