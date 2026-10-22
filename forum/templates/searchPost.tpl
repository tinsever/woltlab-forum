<dl>
	<dt><label for="forumSearchBoardIDs">{lang}forum.search.boards{/lang}</label></dt>
	<dd>
		<select name="boardIDs[]" id="forumSearchBoardIDs" multiple size="10">
			{foreach from=$forumSearchBoards item=boardNode}
				<option value="{$boardNode->board->boardID}"{if $boardNode->board->boardID|in_array:$forumSearchBoardIDs} selected{/if}>{section name=i loop=$boardNode->depth}&nbsp;&nbsp;&nbsp;&nbsp;{/section}{$boardNode->board->getTitle()}</option>
			{/foreach}
		</select>
		<small>{lang}forum.search.boards.description{/lang}</small>
	</dd>
</dl>
{if $forumSearchThreadID}
	<dl>
		<dt></dt>
		<dd><label><input type="checkbox" name="threadID" value="{$forumSearchThreadID}" checked> {lang}forum.search.searchedThread{/lang}</label></dd>
	</dl>
{/if}
