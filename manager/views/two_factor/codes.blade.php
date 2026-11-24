{{-- New recovery codes, shown once: only their hashes are stored. --}}
@include('manager::two_factor.styles')
<div class="evo-2fa">
    <h2 class="evo-2fa__title">{{ __('global.two_factor_codes') }}</h2>
    @include('manager::two_factor.codelist')
    <div class="evo-2fa__buttons">
        <a class="btn btn-success" href="{{ $url }}">{{ __('global.two_factor_continue') }}</a>
    </div>
</div>
