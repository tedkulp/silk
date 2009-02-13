<h3>Blog Posts</h3>

{if count($posts) > 0}
	<table cellspacing="0" class="pagetable">
		<thead>
			<tr>
				<th>Title</th>
				<th>Post Date</th>
				<th>Category</th>
				<th>Author</th>
				<th>Status</th>
				<th class="pageicon">&nbsp;</th>
				<th class="pageicon">&nbsp;</th>
				<th class="pageicon">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
		{foreach from=$posts item='entry'}
			<tr class="{cycle values='row1,row2' advance=false name='post'}" onmouseover="this.className='{cycle values='row1,row2' advance=false name='article'}hover';" onmouseout="this.className='{cycle values='row1,row2' name='post'}';">
				<td>{$entry->title}</td>
				<td>{$entry->post_date}</td>
				<td>Categories w/ Links</td>
				<td>Author</td>
				<td>{$entry->status}</td>
				<td>{link controller='view' action='detail' text='View' url=$entry->url}</td>
				<td>{link action='edit' text='Edit' id=$entry->id controller="admin"}</td>
				<td>{link action='delete' text='Delete' id=$entry->id confirm_text="Are you sure?"}</td>
			</tr>
		{/foreach}
		</tbody>
	</table>
{else}
	<p><strong>No Posts</strong></p>
{/if}