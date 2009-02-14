{validation_errors for=$user_session}

{form}

<div id="regular" {if $params.login.openid ne '' or $smarty.request.openid_mode ne ''}style="display: none;"{/if}>
{label for="login[username]"}Username:{/label}{textbox name="login[username]" value=$params.login.username}<br />
{label for="login[password]"}Password:{/label}{password name="login[password]" value=$params.login.password}<br />

<a href="#" onclick="$('#regular').hide();$('#openid').show();return false;">Use OpenID</a>
</div>

<div id="openid" {if $params.login.openid eq '' and $smarty.request.openid_mode eq ''}style="display: none;"{/if}>
{label for="login[openid]"}Open ID:{/label}{textbox name="login[openid]" value=$params.login.openid}<br />
<a href="#" onclick="$('#regular').show();$('#openid').hide();return false;">Use Username/Password</a>
</div>

{submit value="Submit"}

{/form}