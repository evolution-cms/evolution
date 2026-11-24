<p>{{ __('global.two_factor_codes_message') }}</p>
<ul class="evo-2fa__codes">
    @foreach ($codes as $code)
        <li>{{ $code }}</li>
    @endforeach
</ul>
<p>
    <a class="evo-2fa__link" download="recovery-codes.txt" href="data:text/plain;charset=utf-8,{{ rawurlencode(implode("\n", $codes) . "\n") }}">{{ __('global.two_factor_codes_download') }}</a>
</p>
