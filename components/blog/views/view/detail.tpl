{if $post ne null}
<h3>{$post->title}</h3>
<small>
  {$post->post_date} 
  {if $post->author ne null}
    by {$post->author->full_name()}
  {/if}<br />
  <a href="{$base_url}{$post->url}#disqus_thread">View Comments</a>
</small>
<br />
<br />
<div>
{$post->content}
</div>

<br />
<br />
<hr />

{literal}
<div id="disqus_thread"></div>
<script type="text/javascript" src="http://disqus.com/forums/silkframework/embed.js"></script><noscript><a href="http://silkframework.disqus.com/?url=ref">View the discussion thread.</a></noscript><a href="http://disqus.com" class="dsq-brlink">blog comments powered by <span class="logo-disqus">Disqus</span></a>
<script type="text/javascript">
//<![CDATA[
(function() {
		var links = document.getElementsByTagName('a');
		var query = '?';
		for(var i = 0; i < links.length; i++) {
			if(links[i].href.indexOf('#disqus_thread') >= 0) {
				query += 'url' + i + '=' + encodeURIComponent(links[i].href) + '&';
			}
		}
		document.write('<script charset="utf-8" type="text/javascript" src="http://disqus.com/forums/silkframework/get_num_replies.js' + query + '"></' + 'script>');
	})();
//]]>
</script>

{/literal}

{else}
<p>Post Not Found!</p>
{/if}