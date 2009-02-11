<ul>
	{foreach from=$rss_entries->items item='item'}
		<li>
			<a href="{$item.link}" title="{$item.title}">{$item.title}</a><br />
			<small>
				{strip}
				{if $item.updated}
					{$item.updated|date_format:"%Y/%m/%d %H:%M"}
				{elseif $item.date_timestamp}
					{$item.date_timestamp|date_format:"%Y/%m/%d %H:%M"}
				{/if}
				{/strip}
			</small>
		</li>
	{/foreach}
</ul>