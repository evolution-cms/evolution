## Controllers
Controller is the class extending \FormLister\Core base class, which perfoms:

- loading classes to validate data and generate captcha;
- processing form data (form data here is the value of formData property, not only of $_REQUEST array);
- processing form template and success template.

The way it works:

1. Data are loaded from form.
2. Data are loaded from external sources.
3. Snippets are called to process data.
4. Data validation if it's received from form;
3. Snippets are called to process data again.
6. Final processing - if data came from form and passed validation successfully.
7. Output.

Final processing is done by controller's process() method. Result flag has to be set with setFormStatus() method after successful processing, then fill renderTpl property with template to output processing results. 

These are some base controllers, feel free to extend them:

### Form
Sends e-mail using form data.

### Login
Authorizes users.

### Register
Creates users and sends needed notifications.

### Activate
Processes a special link to confirm user registration or sends it via e-mail.

### DeleteUser
Allows users to delete their profiles. It requests password to confirm.

### Profile
Allows users to edit their profiles.

### Reminder
Helps users to remind their passwords.

### Content
Allows to create and edit resources with MODxAPI classes.

### DeleteContent
Allows users to delete resources they created.

### MailChimp
Adds users to  MailChimp mailing lists. It's provided as example of  \FormLister\Core class extension.
