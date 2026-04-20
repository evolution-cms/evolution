<label id="FMP-email_label" for="FMP_email">[%account_email%]:</label>
<input id="FMP-email" type="text" style="border-radius: 20px!important;">
<button id="FMP-email_button" type="button" style="border-radius: 20px"
        onclick="window.location = 'index.php?action=send_email&email='+encodeURIComponent(document.getElementById('FMP-email').value);">
    [%send%]
</button>