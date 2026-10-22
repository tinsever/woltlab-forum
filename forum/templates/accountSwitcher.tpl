{include file='userMenuSidebar'}

{capture assign='contentHeaderNavigation'}
	{if $canLink}
		<li>
			{if $needsReauthentication}
				<a href="{$reauthenticationLink}" class="button buttonPrimary">
					{icon name='link'}
					<span>{lang}forum.accountSwitcher.link{/lang}</span>
				</a>
			{else}
				<button type="button" class="button buttonPrimary forumAccountSwitcherLinkButton" data-endpoint="{link controller='AccountSwitcherLinkDialog' application='forum'}{/link}">
					{icon name='link'}
					<span>{lang}forum.accountSwitcher.link{/lang}</span>
				</button>
			{/if}
		</li>
	{/if}
{/capture}

{include file='header' __sidebarLeftHasMenu=true}

<section class="section">
	<p>{lang}forum.accountSwitcher.description{/lang}</p>
	{if !$isAvailable}
		<woltlab-core-notice type="warning">{lang}forum.accountSwitcher.notAvailable{/lang}</woltlab-core-notice>
	{elseif !$canLink && FORUM_ACCOUNT_SWITCHER_MAX_ACCOUNTS}
		<woltlab-core-notice type="info">{lang limit=FORUM_ACCOUNT_SWITCHER_MAX_ACCOUNTS}forum.accountSwitcher.link.error.limit{/lang}</woltlab-core-notice>
	{/if}
</section>

{if $accounts|count}
	<section class="section sectionContainerList">
		<ol class="containerList forumAccountSwitcherList">
			<li class="forumAccountSwitcherList__item forumAccountSwitcherList__item--active">
				{include file='__accountSwitcherListItem' application='forum' account=$activeAccount isActive=true}
			</li>
			{foreach from=$accounts item=account}
				<li class="forumAccountSwitcherList__item" data-user-id="{$account->userID}">
					{include file='__accountSwitcherListItem' application='forum' isActive=false}
				</li>
			{/foreach}
		</ol>
	</section>
{else}
	<woltlab-core-notice type="info">{lang}forum.accountSwitcher.noAccounts{/lang}</woltlab-core-notice>
{/if}

<script data-relocate="true">
	require(["Forum/Ui/User/AccountSwitcherPage"], ({ setup }) => {
		setup({if $openLinkDialog && $canLink && !$needsReauthentication}true{else}false{/if});
	});
</script>

{include file='footer'}
