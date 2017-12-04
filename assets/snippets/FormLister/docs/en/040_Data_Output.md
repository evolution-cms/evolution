## Data output

Data are sanitized, arrays are converted to strings to be output in templates. Special placeholders are set for form controls.

Raw field value:
[+field name+]

Sanitized field value: 
```
[+field name.value+]
```

Setting checkbox: 
```
[+c.field name.field value+]
```

Setting select or radio-button: 
```
[+s.field name.field value+]
```

Class for empty required field:
[+field name.requiredСlass+]

Class for wrong filled field:
[+field value.errorClass+]

Alternative classes output:
[+field value.class+]
[+field value.classnames+]

Validation error message:
[+field name.error+]

Controller messages output:
[+form.messages+]

There are 3 possible types of messages in the [+form.messages+] placeholder: failed the "required" rule fields, wrong filled fields, any messages set by addMessage() method. The last ones are output by default, see the "messagesTpl" parameter description.

Lexicon entries:
[%lexicon keys%]

If EvoTwig plugin is used then template variables are available: FormLister (controller object), errors (formData['errors'] array), messages (formData['messages'] array), data (formData['fields'] array).
