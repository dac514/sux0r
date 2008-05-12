<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta content="text/html; charset=UTF-8" http-equiv="content-type" />
    <title>suxRegister</title>
</head>

<body>


{if $validate.default.is_error !== false}
<p>The form was not submitted, see errors below:</p>
{/if}
<p />


<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

{$r->text.nickname} :
{validate id="nickname" message="[ Nickname cannot be empty ]"}
{validate id="nickname2" message="[ Duplicate nickname ]"}
<input type="text" name="nickname" value="{$nickname}" />
<p />

{$r->text.email} :
{validate id="email" message="[ Invalid email ]"}
{validate id="email2" message="[ Duplicate email ]"}
<input type="text" name="email" value="{$email}" />
<p />


{if $r->bool.openid}

    Openid: {$r->text.openid_url}

{else}

    {$r->text.password} :
    {validate id="password" message="[ Passwords do not match ]"}
    <input type="password" name="password" value="{$password}" />
    <p />

    {$r->text.password_verify} :
    <input type="password" name="password_verify" value="{$password_verify}" />

{/if}
<p />

{$r->text.given_name} :
<input type="text" name="given_name" value="{$given_name}" />
<p />

{$r->text.family_name} :
<input type="text" name="family_name" value="{$family_name}" />
<p />

{$r->text.street_address} :
<input type="text" name="street_address" value="{$street_address}" />
<p />

{$r->text.locality} :
<input type="text" name="locality" value="{$locality}" />
<p />

{$r->text.region} :
<input type="text" name="region" value="{$region}" />
<p />

{$r->text.postcode} :
<input type="text" name="postcode" value="{$postcode}" />
<p />

{$r->text.country} :
{html_options name='country' options=$r->text.countries selected=$country}
<p />

{$r->text.tel} :
<input type="text" name="tel" value="{$tel}" />
<p />

{$r->text.url} :
<input type="text" name="url" value="{$url}" />
<p />

{$r->text.dob} :
{* <input type="text" name="dob" value="{$dob}" /> TODO: Javascript calendar *}
{html_select_date time="$Date_Year-$Date_Month-$Date_Day" field_order='YMD' start_year='-100' reverse_years=true year_empty='---' month_empty='---' day_empty='---'}
<p />

{$r->text.gender} :
{html_radios name='gender' options=$r->text.genders selected=$gender assign=tmp}
{foreach from=$tmp item=v}
    <span class="someClass">{$v}</span>
{/foreach}
<p />

{$r->text.language} :
{html_options name='language' options=$r->text.languages selected=$language}
<p />

{$r->text.timezone} :
{html_options name='timezone' options=$r->text.timezones selected=$timezone}
<p />

<input type="submit" value="{$r->text.submit}" />
<p />


</form>


</body>
</html>