<ul>
	{foreach from=$rss_entries->items item='item'}
		<li>{$item.title} - {$item.date_timestamp|date_format:"%c"}</li>
	{/foreach}
</ul>