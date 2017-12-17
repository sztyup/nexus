<!-- Cross domain login handling -->
@foreach($sites as $site)
    <img src="{{ $site->route('auth.internal', ['s_code' => $code]) }}" class="nothing"/>
@endforeach
<!-- End of cross domain login handling -->