{if $__wcf->getLinkedAccountHandler()->isAvailable()}
	{assign var='__accountSwitcherUnread' value=$__wcf->getLinkedAccountHandler()->getUnreadCountOfOtherAccounts()}
	<li id="accountSwitcher" data-count="{$__accountSwitcherUnread}">
		<a
			class="jsTooltip"
			href="{link controller='AccountSwitcher'}{/link}"
			title="{lang}wcf.accountSwitcher.title{/lang}"
			role="button"
			tabindex="0"
			aria-haspopup="true"
			aria-expanded="false"
		>
			{icon size=32 name='arrow-right-arrow-left' type='solid'}
			<span>{lang}wcf.accountSwitcher.title{/lang}</span>
			{if $__accountSwitcherUnread}
				<span class="badge badgeUpdate">{#$__accountSwitcherUnread}</span>
			{/if}
		</a>
		<script data-relocate="true">
			require(["TinSever/AccountSwitcher/Menu"], ({ setup }) => {
				WoltLabLanguage.registerPhrase('wcf.accountSwitcher.title', '{jslang}wcf.accountSwitcher.title{/jslang}');
				WoltLabLanguage.registerPhrase('wcf.accountSwitcher.link', '{jslang}wcf.accountSwitcher.link{/jslang}');
				WoltLabLanguage.registerPhrase('wcf.accountSwitcher.manage', '{jslang}wcf.accountSwitcher.manage{/jslang}');
				WoltLabLanguage.registerPhrase('wcf.accountSwitcher.search.placeholder', '{jslang}wcf.accountSwitcher.search.placeholder{/jslang}');
				setup({
					manageLink: '{link controller='AccountSwitcher' encode=false}{/link}',
					linkLink: '{link controller='AccountSwitcher' encode=false}link=1{/link}',
					enableSearch: {if ACCOUNT_SWITCHER_ENABLE_SEARCH}true{else}false{/if},
				});
			});
		</script>
	</li>
{/if}
