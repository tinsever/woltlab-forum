<dl class="plain dataList">
	<dt>{lang}forum.board.threads{/lang}</dt>
	<dd>{#$stats[threads]}</dd>
	<dt>{lang}forum.board.posts{/lang}</dt>
	<dd>{#$stats[posts]}</dd>
	<dt>{lang}forum.board.statistics.postsPerDay{/lang}</dt>
	<dd>{#$stats[postsPerDay]}</dd>
	<dt>{lang}forum.board.statistics.members{/lang}</dt>
	<dd>{#$stats[members]}</dd>
	{if $newestMember}
		<dt>{lang}forum.board.statistics.newestMember{/lang}</dt>
		<dd>{user object=$newestMember}</dd>
	{/if}
</dl>
