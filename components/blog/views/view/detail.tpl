{if $post ne null}
<h3>{$post->title}</h3>
<small>
  {$post->post_date} 
  {if $post->author ne null}
    by {$post->author->full_name()}
  {/if}
</small>

<div>
{$post->content}
</div>

<hr />

{else}
<p>Post Not Found!</p>
{/if}