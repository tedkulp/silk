<h3>Edit Post</h3>

{validation_errors for=$blog_post}

{form action=$form_action}

	<p>
		{label for="blog_post[title]"}Title:{/label}
		{textbox name="blog_post[title]" value=$blog_post->title size="40"}
	</p>
	
	<p>
		{label for="blog_post[content]"}Content:{/label}
		{textarea name="blog_post[content]" value=$blog_post->content cols="40" rows="10"}
	</p>
	
	<p>
		<fieldset>
			<legend>Categories</legend>
			{foreach from=$categories item='one_category'}
				{assign var=category_id value=$one_category->id}
				{assign var=category_name value=$one_category->name}
				{checkbox name="blog_post[categories][$category_id]" checked=$blog_post->in_category($category_id)} {$category_name}<br />
			{/foreach}
		</fieldset>
	</p>
	
	<p>
		{label for="blog_post[summary]"}Optional Summary:{/label}
		{textarea name="blog_post[summary]" value=$blog_post->summary cols="40" rows="4"}
	</p>
	
	<p>
		{label name="blog_post[status]"}Status:{/label}
		{select name="blog_post[status]"}
			{options items='draft,Draft,publish,Published' selected_value=$blog_post->status}
		{/select}
	</p>
	
	{*
	<p>
		{mod_label name="blog_post[processor]"}{tr}Text Processor{/tr}{/mod_label}:<br />
		{mod_dropdown name="blog_post[processor]" items=$processors selected_value=$blog_post->processor}
	</p>
	*}
	
	<p>
		{label name='post_date_Month'}Post Date:{/label}
		{html_select_date prefix=$post_date_prefix time=$blog_post->post_date->timestamp() start_year=2000 end_year=2020} {html_select_time prefix=$post_date_prefix time=$blog_post->post_date->timestamp()}
	</p>
	
	<p>
		{hidden name="blog_post[author_id]" value=$blog_post->author_id}
		{submit name="submitpost" value='Submit'}
		{submit name="cancelpost" value='Cancel'}
		{if $blog_post->id gt 0}
			{hidden name="id" value=$blog_post->id}
		{/if}
		{submit name="submitpublish" value='Publish'}
	</p>

{/form}
