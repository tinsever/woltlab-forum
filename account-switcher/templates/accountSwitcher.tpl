{include file='userMenuSidebar'}

{capture assign='contentHeaderNavigation'}
	{if $canLink}
		<li>
			{if $needsReauthentication}
				<a href="{$reauthenticationLink}" class="button buttonPrimary">
					{icon name='link'}
					<span>{lang}wcf.accountSwitcher.link{/lang}</span>
				</a>
			{else}
				<button type="button" class="button buttonPrimary accountSwitcherLinkButton" data-endpoint="{link controller='AccountSwitcherLinkDialog'}{/link}">
					{icon name='link'}
					<span>{lang}wcf.accountSwitcher.link{/lang}</span>
				</button>
			{/if}
		</li>
	{/if}
{/capture}

{include file='header' __sidebarLeftHasMenu=true}

<section class="section">
	<p>{lang}wcf.accountSwitcher.description{/lang}</p>
	{if !$isAvailable}
		<woltlab-core-notice type="warning">{lang}wcf.accountSwitcher.notAvailable{/lang}</woltlab-core-notice>
	{elseif !$canLink && ACCOUNT_SWITCHER_MAX_ACCOUNTS}
		<woltlab-core-notice type="info">{lang limit=ACCOUNT_SWITCHER_MAX_ACCOUNTS}wcf.accountSwitcher.link.error.limit{/lang}</woltlab-core-notice>
	{/if}
</section>

{if $accounts|count}
	<section class="section sectionContainerList">
		<ol class="containerList accountSwitcherList">
			<li class="accountSwitcherList__item accountSwitcherList__item--active">
				{include file='__accountSwitcherListItem' account=$activeAccount isActive=true}
			</li>
			{foreach from=$accounts item=account}
				<li class="accountSwitcherList__item" data-user-id="{$account->userID}">
					{include file='__accountSwitcherListItem' isActive=false}
				</li>
			{/foreach}
		</ol>
	</section>
{else}
	<woltlab-core-notice type="info">{lang}wcf.accountSwitcher.noAccounts{/lang}</woltlab-core-notice>
{/if}

<script data-relocate="true">
	require(["TinSever/AccountSwitcher/Page"], ({ setup }) => {
		setup({if $openLinkDialog && $canLink && !$needsReauthentication}true{else}false{/if});
	});
</script>

{include file='footer'}
