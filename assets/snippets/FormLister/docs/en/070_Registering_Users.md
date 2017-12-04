## Регистрация пользователей

Register controller allows to register users, add them to user groups and send notifications. It extends Form controller, so you can use all its parameters to send letters during registration.

Field names should be the same as [modUsers](http://docs.evolution-cms.com/Extras/Snippets/DocLister/MODxAPI) model's ones.

If there's no "username" field in the form, then its value will be the value of the "email" field. So it's possible to register users only by e-mail.

If there's no "password" field, then its value will be created automatically. So, the registration adds up to specify e-mail only.

If there's the "repeatPassword" field in the form and validation rules are set for the "password" and "repeatPassword" fields, then the "equals" rule will be corrected to check if the "password" field matches the "repeatPassword" field:
:
```
"repeatPassword":{
    "required":"Enter password one more time",
    "equals":{
        "params" : "This key is not needed because it will be set by Register controller",
        "message":"Passwords don't match"
    }
}
```

Registration need to check if the "username" and "email" fields are unique. Controller provides rules needed:
```
&rules=`{
    "username":{
        "required":"Enter user name",
        "alphaNumeric":"Only letters and digits are allowed",
        "custom":{
            "function":"\\FormLister\\Register::uniqueUsername",
            "message":"You cannot use this name"
        }
    },
    "email":{
        "required":"Enter e-mail",
        "email":"Wrong e-mail",
        "custom":{
            "function":"\\FormLister\\Register::uniqueEmail",
            "message":"You cannot use this e-mail"
        }
    }
}`
```
All model fields for the new record are available in templates. Additional field "user.password" with given password is set. 

## Parameters
### model
Class to manage users.

Possible values - class name.

Default value - \modUsers

### modelPath
Path to the class to manage users.

Possible values - relative file path.

Default value - assets/lib/MODxAPI/modUsers.php

### allowedFields
Fields allowed to process, other fields are ignored. The "username", "email" and "password" fields are enabled always.

If not set, then all fields are allowed.

Possible value - field names, comma separated. 

Default value - none.

### forbiddenFields
Fields forbidden to process. The "username", "email" and "password" fields will be removed from the list of forbidden fields.

Possible value - field names, comma separated. 

Default value - none.

### userGroups
Adds registered user to user group.

Possible values - group names, comma separated (or an array).

Default value - none.

### checkActivation
Enables the check for user profile activation (see "Activating user profiles"). The "activate.url" field will be set, which contains link to a page with FormLister call to activate user profile.  

Possible values - 1 or 0.

Default value - 0.

### activateTo
If profile activation check is enabled, then you have to specify the id of the page with FormLister call to activate user profile.

Possible values - page id.

Default value - the value of $modx->config['site_start'].

### preparePostProcess
Allows to process data after saving new user.

Possible values - snippet names, anonymous functions, static methods of loaded classes.

Default value - none.

### redirectTo
Redirects user after successful registration.

Possible values - target page id or array.

Default value - none.

### exitTo
Redirects authorized user.

Possible values - target page id or array.

Default value - none.

### skipTpl
Outputs message if user is authorized.

Possible values - template name, according to DocLister templating rules.

Default value - register lexicon entry with the key [%register.default_skipTpl%].

### successTpl
Success message template.

Possible values - template name, according to DocLister templating rules.

Default value - register lexicon entry with the key [%register.default_successTpl%]

### passwordLength
Password length (if the password needs to be created automatically).

Possible values - number of characters greater than 6.

Default value - 6.
