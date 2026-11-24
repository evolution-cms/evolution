{{-- Shared by the login design, the website page and the account page; colours are inherited. --}}
<style>
    .evo-2fa p { margin: 0 0 1rem; line-height: 1.5; }
    .evo-2fa .form-control { width: 100%; border-radius: 20px !important; margin-bottom: 1.25rem; font-size: 1.1rem; letter-spacing: .08em; }
    .evo-2fa label { display: block; margin-bottom: .5em; }
    .evo-2fa__title { margin: 0 0 1rem; font-size: 1.15rem; font-weight: 600; }
    .evo-2fa__error { padding: .5rem .75rem; border-radius: .25rem; color: #fff; background-color: #b71c1c; }
    .evo-2fa__qr { margin: 0 0 1rem; text-align: center; }
    .evo-2fa__qr svg { display: inline-block; width: 200px; max-width: 100%; height: auto; border-radius: .25rem; }
    .evo-2fa__secret, .evo-2fa__codes { font-family: monospace; word-break: break-word; }
    .evo-2fa__codes { display: grid; grid-template-columns: repeat(auto-fit, minmax(11rem, 1fr)); gap: .25rem 1rem; margin: 0 0 1rem; padding: .75rem; border-radius: .25rem; list-style: none; color: #222; background-color: #fff; font-size: .95rem; }
    .evo-2fa__buttons { display: flex; flex-wrap: wrap; gap: .5rem; align-items: center; }
    .evo-2fa__link { padding: .3rem .5rem !important; border: 0 !important; color: inherit !important; background: none !important; text-decoration: underline !important; cursor: pointer; box-shadow: none !important; }
    .evo-2fa details { margin-top: 1rem; }
    .evo-2fa summary { margin-bottom: .75rem; cursor: pointer; }
    .loginbox .evo-2fa { color: #dfe3e6; }
    .loginbox .evo-2fa label, .loginbox .evo-2fa summary { color: inherit; }
    .loginbox .evo-2fa .form-control { color: #fff; background-color: rgba(255, 255, 255, .2); border-width: 0; }
    .loginbox .evo-2fa .btn-success { border-radius: 20px; padding: .5rem 1.25rem; font-size: 1rem; }
    /* narrower than this the login box is dark whatever its style */
    @media (min-width: 480px) {
        .loginbox.loginbox-light .evo-2fa { color: #444; }
        .loginbox.loginbox-light .evo-2fa .form-control { color: #444; background-color: rgba(255, 255, 255, .9) !important; border: 1px solid rgba(113, 116, 117, .4) !important; }
    }
</style>
