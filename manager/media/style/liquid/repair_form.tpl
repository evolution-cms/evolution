<label id="FMP-email_label" for="FMP_email">[%account_email%]:</label>
<input id="FMP-email" type="text" class="form-control">
<button id="FMP-email_button" type="button" class="btn btn-success"
        onclick="window.location = 'index.php?action=send_email&email='+encodeURIComponent(document.getElementById('FMP-email').value);">
    [%send%]
</button>