{capture assign='right_content'}
	<h4>Previous Months</h4>
	<ul>
		{foreach from=$prev_months item='item'}
			<li>
				<a href="{$base_url}{$item.post_year}/{$item.post_month|string_format:"%02d"}">{$item.post_date|date_format:"%B, %Y"}</a><br />
			</li>
		{/foreach}
	</ul>
	<br /><br />
	<h4>Categories</h4>
	<ul>
		{foreach from=$categories item='item'}
			<li>
				<a href="{$base_url}category/{$item.slug}">{$item.name}</a><br />
			</li>
		{/foreach}
	</ul>
{/capture}
{if count($posts)}
	{foreach from=$posts item='entry'}
	<h4><a href="{$base_url}{$entry->url}">{$entry->title}</a></h4>
	<small>
	  {$entry->post_date} 
	  {if $entry->author ne null}
	    By {$entry->author->full_name()}
	  {/if}
	</small>

	<div>
	{$entry->get_summary_for_frontend()}
	</div>

	{if $entry->has_more() eq true}
	  <a href="{$base_url}{$entry->url}">Read More &gt;&gt;</a>
	{/if}
	<br />
	<br />
	<br />
	{/foreach}

	{if isset($prev_page)}
		{link text="&lt; &lt; Prev Page" controller='view' page=$prev_page component='blog' action='index' rss=false}
	{/if}
	{if isset($next_page)}
		{link text="Next Page &gt;&gt;" controller='view' page=$next_page component='blog' action='index' rss=false}
	{/if}
{else}
	<h4>No Posts Found</h4>
{/if}