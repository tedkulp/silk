{literal}
	<style type="text/css">
		div.autoform div { clear: both }
		tr.odd td { background-color: #BBBBBB }
		tr.even td { background-color: #DDDDDD }
	</style>
{/literal}
<h1>List Teams</h1>

Using smarty to loop through results.

<table>
<tr>
	<th>Name</th>
	<th>Start - End</th>
	<th>Action</th>
</tr>
{foreach from=$seasons item=season key=key}
{strip}
   <tr class="{cycle values="even, odd"}">
      <td>{$season.name}</td>
      <td>{$season.start_year} - {$season.end_year}</td>
      <td>
	      {foreach from=$season->stages item=entry key=name}
	      {strip}
	      {$entry.name}<br />
	      {/strip}
	      {/foreach}
	  </td>
	  <td><a href=/silk/season_manager/editSeason?id={$season.id}>Edit</a></td>
   </tr>
{/strip}
{/foreach}
</table>

<!-- Probably a better way to do this -->
<a href="/silk/season_manager/createSeason">Create new season</a>