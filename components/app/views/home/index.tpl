{assign var='page' value='index'}
{assign var='show_top' value='true'}
{assign var='page_title' value='Silk Framework :: Home'}

<h3>Latest News</h3>

{assign var='num_articles' value='3'}
{assign var='ignore_paging' value='true'}
{run_action controller='View' action='index'}
