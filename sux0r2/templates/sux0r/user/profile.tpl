<div id="proselytizer">

{$r->text.nickname} : {$r->profile.nickname} <br />
{$r->text.email} : {mailto address=$r->profile.email  encode='javascript'} <br />

{if $r->profile.given_name || $r->profile.family_name}
    {$r->text.name} : {$r->profile.given_name} {$r->profile.family_name}<br />
{/if}

{if $r->profile.street_address}
    {$r->text.street_address} : {$r->profile.street_address} <br />
{/if}

{if $r->profile.locality}
    {$r->text.locality} : {$r->profile.locality} <br />
{/if}

{if $r->profile.region}
    {$r->text.region} : {$r->profile.region} <br />
{/if}

{if $r->profile.postcode}
    {$r->text.postcode} : {$r->profile.postcode} <br />
{/if}

{if $r->profile.country}
    {$r->text.country} : {$r->getCountry($r->profile.country)}<br />
{/if}

{if $r->profile.tel}
    {$r->text.tel} : {$r->profile.tel} <br />
{/if}

{if $r->profile.url}
    {$r->text.url} : {$r->profile.url} <br />
{/if}

{if $r->profile.dob}
    {$r->text.dob} : {$r->profile.dob} <br />
{/if}

{if $r->profile.gender}
    {$r->text.gender} : {$r->getGender($r->profile.gender)} <br />
{/if}

{if $r->profile.language}
    {$r->text.language} : {$r->getLanguage($r->profile.language)} <br />
{/if}

{if $r->profile.timezone}
    {$r->text.timezone} : {$r->profile.timezone} <br />
{/if}


{*
{$r->profile.users_id}
{$r->profile.accesslevel}
{$r->profile.pavatar}
{$r->profile.microid}
*}

</div>
