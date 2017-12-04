## Captcha usage

FormLister can use modified MODX captcha and Google Recaptcha by default.

Set the "captcha" parameter value with the name of captcha files folder (located at assets/snippets/FormLister/lib/captcha/) to enable it:
```
&captcha=`modxCaptcha`
```

The parameter named "captchaParams" contains an array of captcha settings. For example:
```
&captchaParams=`{
"width":200,
"height":120
}`
```

The field name to get captcha value from user is defined by the "captchaField" parameter ("vericode" by default). Validation rule for this field is created automatically.

To output captcha in form template use [+captcha+] placeholder.

### modxCaptcha

Settings:
* width and height - width and height of a captcha image (100 and 60 by default);
* inline - output format. If it's 1, then [+captcha+] placeholder contains an image in base64-format. If it's 0, then placeholder contains the link to connector.php file, which generates captcha image. Default value - 1;
* connectorDir - path to the folder containing connector.php file, if "inline" parameter is set to 0. Default value - assets/snippets/FormLister/lib/captcha/modxCaptcha/;
* errorEmptyCode - error message, if user doesn't enter captcha.
* errorCodeFailed - error message, if user enters wrong value. 

### reCaptcha

Uses Google reCAPTCHA V2. Include the following script in page tempalate to use it:
```
<script src='https://www.google.com/recaptcha/api.js'></script>
```

The value of "captchaField" parameter should be set to "g-recaptcha-response" (see [documentation](https://developers.google.com/recaptcha/docs/verify)). 

Settings:
* secretKey, siteKey - keys to access reCAPTCHA api; 
* size, theme, badge, callback, expired_callback, tabIndex, type - see. [documentation](https://developers.google.com/recaptcha/docs/display#render_param);
* errorCodeFailed - error message if captcha validation failed.
