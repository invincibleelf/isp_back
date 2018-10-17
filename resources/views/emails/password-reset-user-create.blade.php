

<p>
    @if($user->role->name == "student")
        Dear {{$user->studentDetails->firstname}}
    @elseif($user->role->name === "councilor")
        Dear {{$user->councilorDetails->firstname}}
    @endif

</p>
<p>
   You can reset your password using this password reset link:
    <a href="{{ $link }}" target="_blank">{{ $link }}</a>
</p>

