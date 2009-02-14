<h3>Edit Post</h3>

{validation_errors for=$blog_category}

{form action=$form_action}

	<p>
		{label for="blog_category[name]"}Name:{/label}
		{textbox name="blog_category[name]" value=$blog_category->name size="40"}
	</p>
	
	<p>
		{submit name="submitpost" value='Submit'}
		{submit name="cancel" value='Cancel'}
		{if $blog_post->id gt 0}
			{hidden name="id" value=$blog_category->id}
		{/if}
	</p>

{/form}
