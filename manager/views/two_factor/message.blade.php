@include('manager::two_factor.styles')
<div class="evo-2fa">
    <p class="evo-2fa__error" role="alert">{{ $message }}</p>
    <div class="evo-2fa__buttons">
        <a class="btn btn-success" href="{{ $url }}">{{ __('global.two_factor_back') }}</a>
    </div>
</div>
