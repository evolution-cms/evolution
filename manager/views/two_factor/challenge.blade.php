{{-- The second sign-in step. The method's form posts its answer as two_factor_code. --}}
@include('manager::two_factor.styles')
<div class="evo-2fa">
    <h2 class="evo-2fa__title">{{ __('global.two_factor_title') }}: {{ $title }}</h2>
    @if ($error !== '')
        <p class="evo-2fa__error" role="alert">{{ $error }}</p>
    @endif
    @if ($setup)
        <p>{{ __('global.two_factor_setup_intro') }}</p>
    @endif
    <form method="post" action="{{ $action }}" id="evo-2fa-form" autocomplete="off">
        @csrf
        <input type="hidden" name="two_factor" value="confirm">
        {!! $form !!}
        <div class="evo-2fa__buttons">
            <button type="submit" class="btn btn-success">{{ __('global.two_factor_confirm') }}</button>
            @if ($resend)
                <button type="submit" class="evo-2fa__link" name="two_factor" value="resend" formnovalidate>{{ __('global.two_factor_resend') }}</button>
            @endif
            <button type="submit" class="evo-2fa__link" name="two_factor" value="cancel" formnovalidate>{{ __('global.cancel') }}</button>
        </div>
    </form>
    @if ($recovery)
        <details>
            <summary>{{ __('global.two_factor_recovery_use') }}</summary>
            <form method="post" action="{{ $action }}" autocomplete="off">
                @csrf
                <input type="hidden" name="two_factor" value="confirm">
                <label for="two_factor_recovery">{{ __('global.two_factor_recovery_code') }}</label>
                <input type="text" class="form-control" name="two_factor_code" id="two_factor_recovery" autocapitalize="off" spellcheck="false" required>
                <button type="submit" class="btn btn-success">{{ __('global.two_factor_confirm') }}</button>
            </form>
        </details>
    @endif
</div>
