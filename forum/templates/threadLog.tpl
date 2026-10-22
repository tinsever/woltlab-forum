{capture assign='pageTitle'}{lang}forum.thread.log{/lang}: {$thread->topic}{/capture}

{capture assign='contentTitle'}{lang}forum.thread.log{/lang}: {$thread->topic}{/capture}

{include file='header'}

{hascontent}
	<div class="paginationTop">
		{content}{pages print=true assign=pagesLinks application='forum' controller='ThreadLog' id=$thread->threadID link="pageNo=%d"}{/content}
	</div>
{/hascontent}

{if $logs|count}
	<div class="section tabularBox">
		<table class="table">
			<thead>
				<tr>
					<th class="columnText">{lang}wcf.global.title{/lang}</th>
					<th class="columnText">{lang}wcf.user.username{/lang}</th>
					<th class="columnDate">{lang}wcf.global.date{/lang}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$logs item=log}
					<tr>
						<td class="columnText">
							{unsafe:$log}
							{if $log->getAffectedObject()}
								<br><small><a href="{$log->getAffectedObject()->getLink()}">{$log->getAffectedObject()->getTitle()}</a></small>
							{/if}
						</td>
						<td class="columnText">{user object=$log->getUserProfile()}</td>
						<td class="columnDate">{time time=$log->time}</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
{else}
	<woltlab-core-notice type="info">{lang}wcf.global.noItems{/lang}</woltlab-core-notice>
{/if}

{include file='footer'}
