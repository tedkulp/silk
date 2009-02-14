<h3>Blog Categories</h3>

<ul>
<li>{link text='Edit Posts' component='blog' controller='admin' action='index'}</li>
<li>{link text='Edit Categories' component='blog' controller='admin' action='categories'}</li>
</ul>

{if count($categories) > 0}

	<div style="margin-top: 5px; margin-bottom: 10px; text-align: right;">
	{link text="Add Category" action='addcategory' controller='admin' component='blog'}
	</div>
	
	<table cellspacing="0" class="pagetable">
		<thead>
			<tr>
				<th>Name</th>
				<th class="pageicon">&nbsp;</th>
				<th class="pageicon">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$categories item='category'}
			<tr class="{cycle values='row1,row2' advance=false name='post'}" onmouseover="this.className='{cycle values='row1,row2' advance=false name='article'}hover';" onmouseout="this.className='{cycle values='row1,row2' name='post'}';">
				<td>{link action='editcategory' text=$category->name id=$category->id controller="admin"}</td>
				<td>{link action='editcategory' text='Edit' id=$category->id controller="admin"}</td>
				<td>{link action='deletecategory' text='Delete' id=$category->id confirm_text="Are you sure?" controller="admin"}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{else}
	<p><strong>No Categories</strong></p>
{/if}

<div style="margin-top: 5px; margin-bottom: 10px; text-align: right;">
{link text="Add Category" action='addcategory' controller='admin' component='blog'}
</div>