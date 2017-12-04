## Authorizing users

Login controller authorizes registered users using special MODxAPI class to manage them. Users are identified by their names or e-mails (username and email database fields), but it's possible to use alternative ways with "OnWebAuthentication" event. Plugin named "userHelper" performs some related operations: it counts login attempts, registers last login time, checks auto login cookie, blocks users after some unsuccessful tries, logs out users.    

## Controller parameters

### model
Class to manage users.

Possible values - class name.

Default value - \modUsers

### modelPath
Path to the class to manage users.

Possible values - relative file path.

Default value - assets/lib/MODxAPI/modUsers.php

### loginField
Field to identify user.

Possible values - field name.

Default value - username.

### passwordField
Password field.

Possible values - field name.

Default value - password.

### rememberField
Field to remember user. If the field value is equal to true, then a special auto login cookie will be set after successful authorization. Cookie name and its lifetime is defined by the "cookieName" and "cookieLifetime" parameters.

It's possible to set the "rememberme" field in the "defaults" parameter, to enable it by default:
```
&defaults=`{"rememberme":1}`
```

Possible values - field name.

Default value - rememberme.

### checkActivation
Enables check for the profile activation (see "Activating user profiles"). 

Possible values - 0 or 1.

Default value - 1.

### context
Authorization context.

Possible values - mgr or web.

Default value - web.

### cookieName
Cookie name to store auto login parameters.

Default value - WebLoginPE.

### cookieLifetime
Autologin cookie life time.

Possible values - the number of seconds since last login.

Default value - 157680000 (5 years).

### redirectTo
Redirects user after successful authorization.

Possible values - target page id or array.

Default value - none.

### exitTo
Redirects already authorized user.

Possible values - target page id or array.

Default value - none.

### successTpl
Success message template. User data can be used there.

Possible values - template name, according to DocLister templating rules.

Default value - lexicon entry with the key [%login.default_successTpl%]

### skipTpl
Outputs message if user is already authorized.

Possible values - template name, according to DocLister templating rules.

Default value - lexicon entry with the key [%login.default_skipTpl%]

## userHelper plugin parameters
### logoutKey
GET-parameter name to catch for user logout request. For example, http://sitename.ru/page.html?logout.

Default value - logout.

### cookieName
Cookie name to store autologin parameters.

Default value - WebLoginPE.

### cookieLifetime
Autologin cookie life time.

Possible values - number of seconds since last login.

Default value - 157680000 (5 years).

### maxFails
Number of unsuccessful login attempts before the block.

Possible values - number greater than 0.

Default value - 3.

### blockTime
Time to block.

Possible values - number of seconds since last login attempt.

Default value - 3600 (1 hour).

