{{-- The user's own set-up: in the manager (action 202) and on the website. --}}
@include('manager::two_factor.styles')
<div class="evo-2fa">
    @if ($error !== '')
        <p class="evo-2fa__error" role="alert">{{ $error }}</p>
    @endif

    @if ($codes !== [])
        <h2 class="evo-2fa__title">{{ __('global.two_factor_codes') }}</h2>
        @include('manager::two_factor.codelist')
    @endif

    @if ($form !== null)
        <h2 class="evo-2fa__title">{{ $form['title'] }}</h2>
        <form method="post" action="{{ $action }}" id="evo-2fa-form" autocomplete="off" style="max-width: 26rem">
            @csrf
            <input type="hidden" name="two_factor" value="confirm">
            {!! $form['html'] !!}
            <div class="evo-2fa__buttons">
                <button type="submit" class="btn btn-success">{{ __('global.two_factor_confirm') }}</button>
                @if ($form['resend'])
                    <button type="submit" class="evo-2fa__link" name="two_factor" value="resend" formnovalidate>{{ __('global.two_factor_resend') }}</button>
                @endif
                <button type="submit" class="evo-2fa__link" name="two_factor" value="cancel" formnovalidate>{{ __('global.cancel') }}</button>
            </div>
        </form>
        @if ($form['recovery'])
            <details style="max-width: 26rem">
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
    @else
        <form method="post" action="{{ $action }}">
            @csrf
            <p>
                @if (isset($rows[$effective]))
                    {{ __('global.two_factor_status_on', ['method' => $rows[$effective]['title']]) }}
                    @if (!$mayChoose) {{ __('global.two_factor_status_policy') }} @endif
                @else
                    {{ __('global.two_factor_status_off') }}
                @endif
            </p>
            <table class="table data">
                @foreach ($rows as $key => $row)
                    <tr>
                        <td>{{ $row['title'] }}</td>
                        <td>
                            @if ($row['active']) <strong>{{ __('global.two_factor_active') }}</strong> @endif
                            {{ $row['confirmed'] ? __('global.two_factor_confirmed') : __('global.two_factor_not_confirmed') }}
                        </td>
                        <td>
                            @if (!$locked)
                                <div class="evo-2fa__buttons">
                                    <button type="submit" class="btn btn-secondary" name="two_factor" value="setup:{{ $key }}">{{ $row['confirmed'] ? __('global.two_factor_setup_again') : __('global.two_factor_setup') }}</button>
                                    @if ($mayChoose && $row['confirmed'] && !$row['active'])
                                        <button type="submit" class="btn btn-secondary" name="two_factor" value="use:{{ $key }}">{{ __('global.two_factor_use') }}</button>
                                    @endif
                                    @if ($mayChoose && $row['active'])
                                        <button type="submit" class="btn btn-secondary" name="two_factor" value="off">{{ __('global.two_factor_turn_off') }}</button>
                                    @endif
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </table>
            @if ($locked)
                <p>{{ __('global.two_factor_unlock_message') }}</p>
                <button type="submit" class="btn btn-success" name="two_factor" value="unlock">{{ __('global.two_factor_unlock') }}</button>
            @elseif ($canRegenerate)
                <p>{{ __('global.two_factor_codes_left', ['count' => $codesLeft]) }}</p>
                <button type="submit" class="btn btn-secondary" name="two_factor" value="codes">{{ __('global.two_factor_codes_new') }}</button>
            @endif
        </form>
    @endif
</div>
