<h1>Hello, world!</h1>
 
<p>The id is: {$params.id}</p>
 
<p>The controllers wants to say: {$test}</p>
 
{form remote='true' url='/silk/photos/test_ajax'}
 
{textbox name="test" value="test" label="Test Me:"}
 
{select name="blah"}
{options items="0,blah,1,blah 2"}
{/select}
 
{submit}
 
{/form}
 
<div id="some_content">
Some Content
</div>