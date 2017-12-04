## Activating user profiles

Activate controller activates user profile, so it's possible to request registration confirmation by following the special link in the letter sent after the registration.

If the user hasn't received this letter, then he can request this letter again.

User profile assumes to be inactivated if the "logincount" field value is -1.

Snippet calls to register and authorize users should have the  "checkActivation" parameter enabled.

If user creates password himself during registration, then this password needs to be requested to send a letter with the activation link. Or the new password will be created, because if user hasn't received the letter containing generated password after registration, then he cannot know this password

All model fields are available in templates. Additional field "user.password" with raw password value is set as well as the "activate.url" field with activation link. 

## Parameters
### model
Class to manage users.

Possible values - class name.

Default value - \modUsers

### modelPath
Path to the class to manage users.

Possible values - relative file path.

Default value - assets/lib/MODxAPI/modUsers.php

### redirectTo
Redirects user after successful activation.

Possible values - target page id or array.

Default value - none.

### exitTo
Redirects authorized user.

Possible values - target page id or array.

Default value - none.

### skipTpl
Outputs message if user is authorized.

Possible values - template name, according to DocLister templating rules.

Default value - activate lexicon entry with the key [%activate.default_skipTpl%].

### reportTpl
Letter template containing profile activation data.

Possible values - template name, according to DocLister templating rules.

Default value - none.

### successTpl
Message template if e-mail with activation data is sent successfully.

Possible values - template name, according to DocLister templating rules.

Default value - activate lexicon entry with the key [%activate.default_successTpl%]

### activateSuccessTpl
Message template if activation is finished successfully.

Possible values - template name, according to DocLister templating rules.

Default value - activate lexicon entry with the key [+activate.default_activateSuccessTpl+]

### passwordLength
Password length.

Possible values - number of characters greater than 6.

Default value - 6.
