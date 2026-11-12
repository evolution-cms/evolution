<label id="FMP-email_label" for="FMP-email">[%account_email%]:</label>
<input id="FMP-email" type="email" autocomplete="email" style="border-radius: 20px!important;"
       onkeydown="if (event.key === 'Enter') { event.preventDefault(); evoRequestPasswordRecovery(); }">
<button id="FMP-email_button" type="button" style="border-radius: 20px" onclick="evoRequestPasswordRecovery();">
    [%send%]
</button>
<script>
    // Posted on its own: the field sits inside the login form, and the address must not end up in a URL.
    function evoRequestPasswordRecovery() {
        var field = document.getElementById('FMP-email');
        if (field.value.trim() === '') {
            field.focus();
            return;
        }
        var form = document.createElement('form');
        form.method = 'post';
        form.action = 'index.php';
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'repair_email';
        input.value = field.value;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
    }
</script>
