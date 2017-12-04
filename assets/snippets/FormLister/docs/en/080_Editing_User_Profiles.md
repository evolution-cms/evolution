## Editing user profile

Profile controller allow authorized users to change their profiles, as well as passwords.

E-mail should be checked for uniqueness with controller's special rule:
```
&rules=`{
    "email":{
        "required":"Enter e-mail",
        "email":"Wrong e-mail",
        "custom":{
            "function":"\\FormLister\\Profile::uniqueEmail",
            "message":"You cannot use this e-mail"
        }
    }
}`
```
The same for the "username" field:
```
&rules=`{
    "username":{
        "required":"Enter user name",
        "alphaNumeric":"Only letters and digits are allowed",
        "custom":{
            "function":"\\FormLister\\Profile::uniqueUsername",
            "message":"You cannot use this name"
        }
    }
}`
```

If the "password" field is empty, then the password will stay unchanged.If the password is changed then user needs to be authorized again. The new password is stored in the "user.password" field.

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
Fields allowed to process, other fields are ignored. If password is changed, then the "password" field will be added to the list. If no value is set to the "username" field then, it this field be set with the "e-mail" field value. The "username" will be allowed in this case.

If not set, then all fields are allowed.

Possible value - field names, comma separated. 

Default value - none.

### forbiddenFields
Fields forbidden to process. The "password" and "username" fields are processed the same way as for the "allowedFields" parameter.

Possible value - field names, comma separated. 

Default value - none.

### preparePostProcess
Allows to process data after saving user data.

Possible values - snippet names, anonymous functions, static methods of loaded classes.

Default value - none.

### redirectTo
Redirects user after successful profile update.

Possible values - target page id or array.

Default value - none.

### exitTo
Redirects non authorized user.

Possible values - target page id or array.

Default value - none.

### skipTpl
Outputs message if user is not authorized.

Possible values - template name, according to DocLister templating rules.

Default value - profile lexicon entry with the key [%profile.default_skipTpl%].

### successTpl
Success message template.

Possible values - template name, according to DocLister templating rules.

Default value - none.
