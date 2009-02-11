<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

<base href="{php}echo SilkRequest::get_calculated_url_base(true){/php}/"></base>

<script type="text/javascript" src="lib/silk/jquery/jquery.js"></script>
<script type="text/javascript" src="lib/silk/jquery/jquery.color.js"></script>
<script type="text/javascript" src="lib/silk/jquery/jquery.silk.js"></script> 
<script type="text/javascript" src="assets/js/tablesorter/jquery.metadata.js"></script>
<script type="text/javascript" src="assets/js/tablesorter/jquery.tablesorter.js"></script>

<script type="text/javascript" src="assets/js/theme.js"></script>
<link href="assets/css/silk.css" rel="stylesheet" type="text/css" />

<title>{$page_title}</title>
</head>
<body>
<div id="wrap">
<div id="wrap-top">
<div id="logo"><a href="">Silk Framework</a></div>
<div class="top-libs">Libs: Jquery, php, CmsMadeSimple . . .</div>
</div><!--wrap-top-->

<div id="wrap-content" class="clear">
<div id="wrap-menu" class="clear">
<ul id="silk-menu">
<li{if $page eq 'index'} class="menuactive"{/if}><a{if $page eq 'index'} class="menuactive"{/if} href="">Silk Home</a></li>
<li{if $page eq 'about'} class="menuactive"{/if}><a{if $page eq 'about'} class="menuactive"{/if} href="home/about">What is Silk?</a></li>
<!--
<li{if $page eq 'tutorials'} class="menuactive"{/if}><a{if $page eq 'tutorials'} class="menuactive"{/if} href="home/tutorials">Tutorials</a></li>
-->
<li{if $page eq 'development'} class="menuactive"{/if}><a{if $page eq 'development'} class="menuactive"{/if} href="home/development">Development</a></li>
<li><a href="http://silkframework.com/forum/">Forum</a></li>
<li><a href="http://silkframework.com/docs/">Book</a></li>
<li><a href="http://silkframework.com/api/">API</a></li>
</ul>
</div><!--wrap-menu-->

{if $show_top == 'true'}
<div id="wrap-slogan" class="clear">
<div class="slogan"><h1>Silk is a new kind of PHP Framework.</h1>
<h2>Silk is a fast and concise PHP Framework that simplifies development of PHP applications.  It provides services for database 
interaction, MVC, AJAX and all of the other tools necessary to deliver today&apos;s web applications. It&apos;s speedy, extendable, 
dependable and totally buzzword compliant. <a href="home/about">Read More >></a></h2>
</div><!--slogan-->

<!--
<div class="download">
<a href="#">download</a>
<span>Latest Version: 1.0</span>
</div>--><!--download-->
</div><!--wrap-slogan-->
{/if}

<div id="content">
<div id="main">
{if $flash ne ''}
<div id="flash">{$flash}</div>
{/if}
{$content}
</div>
</div><!--content-->
<div id="right">
	{*
	{if $page != 'index'}
	<h4>Latest News</h4>
	{magpie_rss url='http://silkframework.com/forum/index.php?type=rss;action=.xml' assign='rss_entries' number_to_return='5'}
	{render_partial template='rssview.tpl'}<br />
	{/if}
	*}
	<h4>Latest Forum Posts</h4>
	{magpie_rss url='http://silkframework.com/forum/index.php?type=rss;action=.xml' assign='rss_entries' number_to_return='5'}
	{render_partial template='rssview.tpl'}
	<small><a href="http://silkframework.com/forum" title="Silk Forums">Go >></a></small><br /><br />
	<h4>Latest Github Commits</h4>
	{magpie_rss url='http://github.com/feeds/tedkulp/commits/silk/master' assign='rss_entries' number_to_return='5'}
	{render_partial template='rssview.tpl'}
	<small><a href="http://silkframework.com/forum" title="Github Silk Repository">Go >></a></small><br /><br />
</div><!--right-->
</div><!--wrap-content-->


</div><!--wrap-->
<div id="wrap-footer">
<div class="logo-bottom"> </div> 
<div class="right-bottom"> </div> 
</div> <!--wrap-footer-->

{literal}
<script type="text/javascript">
var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
</script>
<script type="text/javascript">
try {
var pageTracker = _gat._getTracker("UA-305038-4");
pageTracker._trackPageview();
} catch(err) {}</script>
<script src="http://silk.uservoice.com/pages/general/widgets/tab.js?alignment=right&amp;color=FF9B00" type="text/javascript"></script>
{/literal}
</body>
</html>
