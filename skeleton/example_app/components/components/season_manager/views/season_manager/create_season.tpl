{literal}
	<style type="text/css">
		div.autoform div { clear: both }
		div.autoform label.block{
	        display: block;
	        width: 120px;
	        _width: 150px;
	        float: left;
	        margin-right: 0px;
	        padding: 5px 0px 3px 0px;
		}
		label.label {
			padding: 20px;
		}
		label.data {
		}

	</style>
{/literal}
{if $savedSeason eq ""}
	<h1>Create a new season</h1>

	{form url='/silk/season_manager/createSeason'}

	<table>
		<tr>
			<td>Name of season:</td>
			<td>{textbox name="seasonName" value="" label=""}</td>
		</tr>
		<tr>
			<td>Starting year:</td>
			<td>{textbox name="startYear" value="" label=""}</td>
		</tr>
		<tr>
			<td>Ending year:</td>
			<td>{textbox name="endYear" value="" label=""}</td>
		</tr>
		<tr>
			<td>Status:</td>
			<td>{select name="status_id"}{options items="0,Inactive,1,Active"}{/select}</td>
		</tr>
		<tr>
			<td>{submit value="Submit"}</td>
		</tr>
	</table>
	{/form}
{else}
	<h1>Season created!</h1>
	{$savedSeason}
{/if}
