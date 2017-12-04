## Deleting user documents

DeleteContent controller allows authorized users to delete documents they created.

It extends Form controller.

Model fields are available in templates with the "user" prefix (user.fullname, user.email etc.).

## Parameters
### model
MODxAPI class to manage documents or other type of records.

Possible values - MODxAPI class name.

Default value - \modResource.

### modelPath
Path to the MODxAPI class.

Possible values - relative file path.

Default value - assets/lib/MODxAPI/modResource.php.

### userModel
Class to manage users.

Possible values - class name.

Default value - \modUsers

### userModelPath
Path to the class to manage users.

Possible values - relative file path.

Default value - assets/lib/MODxAPI/modUsers.php

### ownerField
Field name to get the record owner. For the modResource class it can be the name of a tv-parameter (because web users can not create documents in Evo).

Possible values - field name.

Default value - aid.

### idField
$_REQUEST array key to get the id of the record to be deleted.

Default value - id.

### redirectTo
Redirects user after successful operation.

Possible values - target page id or array.

Default value - none.

### badOwnerTpl
Outputs message if user is not the record owner.

Possible values - template name, according to DocLister templating rules.

Default value - deleteContent lexicon entry with the key  [%deleteContent.default_badOwnerTpl%].

### badRecordTpl
Outputs message if user can not delete record (it doesn't exists, for example).

Possible values - template name, according to DocLister templating rules.

Default value - deleteContent lexicon entry with the key [%deleteContent.default_badRecordTpl%].

### skipTpl
Outputs message if user is not authorized.

Possible values - template name, according to DocLister templating rules.

Default value - deleteContent lexicon entry with the key [%deleteContent.default_skipTpl%].

### successTpl
Success message template.

Possible values - template name, according to DocLister templating rules.

Default value - deleteContent lexicon entry with the key [%deleteContent.default_successTpl%]

### exitTo
Redirects non authorized user.

Possible values - target page id or array.

Default value - none.

### badRecordTo
Redirects if record can not be deleted.

Possible values - target page id or array.

Default value - none.
