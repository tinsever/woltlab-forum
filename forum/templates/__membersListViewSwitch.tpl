{if $__forumMembersListView == 'list'}
	<a href="{$__forumMembersListViewSwitchLink}" class="contentInteractionButton button small" data-forum-members-view="cards">{icon name='table-cells-large'} <span>{lang}forum.user.members.view.cards{/lang}</span></a>
{else}
	<a href="{$__forumMembersListViewSwitchLink}" class="contentInteractionButton button small" data-forum-members-view="list">{icon name='list'} <span>{lang}forum.user.members.view.list{/lang}</span></a>
{/if}
