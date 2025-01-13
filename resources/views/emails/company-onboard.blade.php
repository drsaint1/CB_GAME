<x-mail::message>
# Dear, {{$name}}
 
You are receiving this email because we received a signup request for your this mail account.
 
<x-mail::button :url="$url">
Click Here
</x-mail::button>

Or copy and paste this URL into a new tab of your browser:

[{{ $url }}]({{ $url }})


If you did not request a signup , no further action is required.
 
Thanks,
{{ config('app.name') }}
</x-mail::message>