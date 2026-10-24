{capture assign='pageTitle'}{lang}wcf.user.security.multifactor.authentication{/lang}{/capture}
{capture assign='contentTitle'}{lang}wcf.user.security.multifactor.authentication{/lang}{/capture}
{capture assign='contentDescription'}{if $isLink}{lang}wcf.accountSwitcher.multifactor.description.link{/lang}{else}{lang}wcf.accountSwitcher.multifactor.description{/lang}{/if}{/capture}

{include file='authFlowHeader'}

<div class="accountSwitcherMultifactor__account">
	{unsafe:$target->getAvatar()->getImageTag(48)}
	<span class="accountSwitcherMultifactor__username">{$target->username}</span>
</div>

{unsafe:$form->getHtml()}

<div class="authOtherOptionButtons" hidden>
	{if $setups|count > 1}
		<div class="authOtherOptionButtons__separator">
			{lang}wcf.user.security.multifactor.otherOptions{/lang}
		</div>

		<ul class="authOtherOptionButtons__buttonList">
			{foreach from=$setups item='method'}
				{if $setup->getId() != $method->getId()}
					<li>
						<a
							href="{link controller='AccountSwitcherMultifactor' id=$method->getId()}{/link}"
							class="button authOtherOptionButtons__button"
						>
							{if $method->getObjectType()->icon}
								{icon size=24 name=$method->getObjectType()->icon}
							{else}
								{icon size=24 name='lock'}
							{/if}
							<span>{lang}wcf.user.security.multifactor.{$method->getObjectType()->objectType}{/lang}</span>
						</a>
					</li>
				{/if}
			{/foreach}
		</ul>
	{/if}

	<a href="{if $isLink}{link controller='AccountSwitcher'}{/link}{elseif $returnUrl}{$returnUrl}{else}{link}{/link}{/if}" class="button authOtherOptionButtons__button accountSwitcherMultifactor__cancel">
		<span>{lang}wcf.global.button.cancel{/lang}</span>
	</a>
</div>

<script data-eager="true">
	{
		const container = document.querySelector('.authOtherOptionButtons');
		document.getElementById('accountSwitcherMultifactor').append(container);
		container.hidden = false;
	}
</script>

<script data-relocate="true">
	{
		let wasSubmitted = false;
		const form = document.getElementById("accountSwitcherMultifactor");
		form.addEventListener("submit", () => {
			document.getElementById("submitButton").disabled = true;
			wasSubmitted = true;
		});
		const code = document.getElementById("code") ?? document.getElementById("onetimecode");
		code?.addEventListener("input", () => {
			if (!wasSubmitted && code.value.length === code.maxLength) {
				form.requestSubmit();
				wasSubmitted = true;
			}
		});
	}
</script>

{include file='authFlowFooter'}
