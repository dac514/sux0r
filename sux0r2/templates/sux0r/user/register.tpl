<div id="proselytizer">

<form action="{$r->text.form_url}" name="default" method="post" accept-charset="utf-8">
<input type="hidden" name="token" value="{$token}" />

<fieldset>
<legend>{$r->text.reg}</legend>

{if $validate.default.is_error !== false}
<p class="errorWarning">{$r->text.form_error} :</p>
{/if}

<p>
{strip}
    {capture name=error1}
    {validate id="nickname" message=$r->text.form_error_1}
    {validate id="nickname2" message=$r->text.form_error_2}
    {validate id="nickname3" message=$r->text.form_error_3}
    {/capture}
{/strip}

<label for="nickname" {if $smarty.capture.error1}class="error"{/if} >* {$r->text.nickname} :</label>
<input type="text" name="nickname" value="{$nickname}" />
{$smarty.capture.error1}
</p>

<p>
{strip}
    {capture name=error2}
    {validate id="email" message=$r->text.form_error_4}
    {validate id="email2" message=$r->text.form_error_5}
    {/capture}
{/strip}

<label for="email" {if $smarty.capture.error2}class="error"{/if} >* {$r->text.email} :</label>
<input type="text" name="email" value="{$email}" />
{$smarty.capture.error2}
</p>


{if $r->bool.openid}

    <p>
    <label>OpenID :</label> {$r->text.openid_url}
    </p>

{else}

    <p>
    {strip}
        {capture name=error3}
        {validate id="password" message=$r->text.form_error_6}
        {validate id="password2" message=$r->text.form_error_7}
        {/capture}
    {/strip}

    <label for="password" {if $smarty.capture.error3}class="error"{/if}>* {$r->text.password} :</label>
    <input type="password" name="password" value="{$password}" />
    {$smarty.capture.error3}
    </p>

    <p>
    <label for="password_verify">{$r->text.password_verify} :</label>
    <input type="password" name="password_verify" value="{$password_verify}" />
    </p>

{/if}


<p>
<label for="given_name">{$r->text.given_name} :</label>
<input type="text" name="given_name" value="{$given_name}" />
</p>

<p>
<label for="family_name">{$r->text.family_name} :</label>
<input type="text" name="family_name" value="{$family_name}" />
</p>

<p>
<label for="street_address">{$r->text.street_address} :</label>
<input type="text" name="street_address" value="{$street_address}" />
</p>

<p>
<label for="locality">{$r->text.locality} :</label>
<input type="text" name="locality" value="{$locality}" />
</p>

<p>
<label for="region">{$r->text.region} :</label>
<input type="text" name="region" value="{$region}" />
</p>

<p>
<label for="postcode">{$r->text.postcode} :</label>
<input type="text" name="postcode" value="{$postcode}" />
</p>

<p>
<label for="country">{$r->text.country} :</label>
{html_options name='country' options=$r->getCountries() selected=$country}
</p>

<p>
<label for="tel">{$r->text.tel} :</label>
<input type="text" name="tel" value="{$tel}" />
</p>

<p>
<label for="url">{$r->text.url} :</label>
<input type="text" name="url" value="{$url}" />
</p>

<p>
<label>{$r->text.dob} :</label>
<span class="htmlSelectDate">
{html_select_date time="$Date_Year-$Date_Month-$Date_Day" field_order='YMD' start_year='-100' reverse_years=true year_empty='---' month_empty='---' day_empty='---'}
</span>
</p>


<p>
<label for="gender">{$r->text.gender} :</label>
<span class="htmlRadios">
{html_radios name='gender' options=$r->getGenders() selected=$gender}
</span>
</p>


<p>
<label for="language">{$r->text.language} :</label>
{html_options name='language' options=$r->getLanguages() selected=$language}
</p>

<p>
<label for="timezone">{$r->text.timezone} :</label>
{html_options name='timezone' options=$r->getTimezones() selected=$timezone}
</p>

<p>
<label>&nbsp;</label><input type="submit" class="button" value="{$r->text.submit}" />
</p>

</fieldset>
</form>

</div>