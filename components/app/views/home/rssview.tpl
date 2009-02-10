<ul>
	{foreach from=$rss_entries->items item='item'}
		<li><a href="{$item.link}" title="{$item.title}">{$item.title}</a><br /><small>{if $item.updated}{$item.updated|date_format:"%c"}{/if}{if $item.date_timestamp}{$item.date_timestamp|date_format:"%Y/%M/%d %H:%M"}{/if}</small></li>
	{/foreach}
</ul>