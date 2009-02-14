{assign var='page' value='index'}
{assign var='show_top' value='true'}
{assign var='page_title' value='Silk Framework :: Home'}

<h3>Latest News</h3>

<!--
<h4>Site Launched!</h4>
<small>Posted by Ted Kulp - 2009/02/11 08:39</small><br />
<p>
It&apos;s official!  Silk Framework is a now a working open source project.  We&apos;ve quickly launched a site
so that people who might be interested in the framework will have a place to look for various resources.  While
this is no means complete, it&apos;s a start in the proper direction.
</p>

<p>If you have any questions or comments, please register and use the forums.  If there are things that should
be improved, please click on the Feedback link on the right of every page.</p>

<p>Thanks for your interest.  We&apos;ll have some more information soon!</p>
-->
{assign var='num_articles' value='3'}
{assign var='ignore_paging' value='true'}
{run_action controller='View' action='index'}
