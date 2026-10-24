{if $__forumMembersSortFields[$field]|isset}
	<button type="button" class="forumMembersTable__sort{if $view->getSortField() == $field} forumMembersTable__sort--active{/if}" data-forum-members-sort="{$field}"{if $view->getSortField() == $field} aria-sort="{if $view->getSortOrder() == 'ASC'}ascending{else}descending{/if}"{/if}>
		<span>{lang}{$label}{/lang}</span>
		{if $view->getSortField() == $field}{if $view->getSortOrder() == 'ASC'}{icon name='caret-up'}{else}{icon name='caret-down'}{/if}{/if}
	</button>
{else}
	{lang}{$label}{/lang}
{/if}
