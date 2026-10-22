{if MODULE_USER_RANK && $account->getUserTitle()}
	<span class="badge userTitleBadge{if $account->getRank() && $account->getRank()->cssClassName} {$account->getRank()->cssClassName}{/if}">{$account->getUserTitle()}</span>
{/if}
{if $account->banned}
	<span class="badge red">{lang}forum.accountSwitcher.banned{/lang}</span>
{/if}
