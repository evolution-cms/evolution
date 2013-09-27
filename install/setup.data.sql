# MODX Database Script for New/Upgrade Installations
#
# Each sql command is separated by double lines


#
# Dumping data for table `keyword_xref`
#


REPLACE INTO `{PREFIX}keyword_xref` VALUES ('3','1');


REPLACE INTO `{PREFIX}keyword_xref` VALUES ('4','1');


#
# Dumping data for table `documentgroup_names`
#


REPLACE INTO `{PREFIX}document_groups` VALUES ('1','1','3');


REPLACE INTO `{PREFIX}documentgroup_names` VALUES ('1','Site Admin Pages','0','0');


#
# Dumping data for table `site_content`
#



# Dumping data for table `modx_site_content`
#

REPLACE INTO `{PREFIX}site_content` VALUES ('1','document','text/html','Home','Welcome to MODX','Introduction to MODX','index','','1','0','0','0','0','Create and do amazing things with MODX','<h3>Install Successful!</h3>\n<p>You have successfully installed and configured MODX. We hope you find this site an adequate starting configuration for many small business, organization or personal websites; just change the template and content, and you\'ll be good to go! This site is preconfigured with a variety of options we hope are helpful, relevant and just plain cool for many marketing or personal sites:</p>\n<ul>\n<li><strong>Simple Blog.</strong> When logged into your site, you\'ll be able to create new entries from the front end. This can also be turned into a News publishing or PR publishing system. <a href=\"[~2~]\">View example blog</a></li>\n<li><strong>Easy Comments.</strong> When logged into your site, your registered site users can comment on your posts. <a href=\"[~9~]\">View example</a></li>\n<li><strong>RSS Feeds.</strong> Your site visitors can stay up to date using your site feeds. <a href=\"feed.rss\">View RSS feed</a></li>\n<li><strong>Automatic User Registration.</strong> Those that wish to comment on blogs must first create an account. This comes pre-configured with a \"Captcha\" anti-robot registration feature. <a href=\"[~5~]\">View registration form</a></li>\n<li><strong>QuickEdit.</strong> When you\'re logged into the manager, you can edit a page directly from the front end! <a href=\"[~14~]\">More about CMS features</a></li>\n<li><strong>Integrated Site Search.</strong> Allows visitors to search only the pages you wish them to search. Uses Ajax to display results without loading a new page.</li>\n<li><strong>Powerful Navigation Builder.</strong> Duplicate or build virtually any navigation system with our dynamic menu builder code. The menu above, for example. <a href=\"[~22~]\">More about menu features</a></li>\n<li><strong>Mootools enabled.</strong> You\'re on your way to Web 2.0 and AJAX goodness. <a href=\"[~16~]\">More about Ajax features</a></li>\n<li><strong>Custom \"page not found (404)\" page.</strong> Help visitors who go astray to find what they\'re looking for. <a href=\"[~7~]\">View 404 page</a></li>\n<li><strong>Contact Us form.</strong> A highly configurable contact form you <em>should</em> customize to point to the right email address. Comes pre-configured to prevent mail-form-injection so your site does <em>not</em> become a source for spam. <a href=\"[~6~]\">View form</a></li>\n<li><strong>Newest documents list.</strong> Shows your visitor the most recently added pages (configurable).</li>\n<li><strong>Customizable Content Manager.</strong> Preview uploaded images, hide or rename fields and lots more. See <a href=\"assets/plugins/managermanager/docs/index.htm\">ManagerManager\'s documentation</a> for details and instructions. <em>Quick start:</em> ManagerManager by default will look for \"rules\" in a Chunk named \"mm_rules\". Simply copy or rename the Chunk named \"mm_demo_rules\" to \"mm_rules\" and try it out!</li>\n</ul>\n<p><strong>To log into the MODX Control Panel and start customizing this site, point your browser to <a href=\"manager\">[(site_manager_url)]</a>.</strong></p>','1','4','0','1','1','1','1144904400','1','1378084284','0','0','0','0','0','Home','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('2','document','text/html','Blog','My Blog','','blog','','1','0','0','0','1','','[[Ditto? &parents=`2` &display=`2` &removeChunk=`Comments` &tpl=`ditto_blog` &paginate=`1` &extenders=`summary,dateFilter` &paginateAlwaysShowLinks=`1` &tagData=`documentTags`]]\n\n<p>Showing <strong>[+start+]</strong> - <strong>[+stop+]</strong> of <strong>[+total+]</strong> Articles</p>\n\n<div id=\"ditto_pages\"> [+previous+] [+pages+] [+next+] </div>\n\n<div id=\"ditto_pages\">&nbsp;</div>\n\n[[Reflect? &config=`wordpress` &dittoSnippetParameters=`parents:2` &id=`wp` &getDocuments=`1`]]','1','4','1','0','0','1','1144904400','1','1159818696','0','0','0','0','0','Blog','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('4','document','text/html','[*loginName*]','Login to Enable to Comments','','login','','1','0','0','0','0','','<p>In order to comment on blog entries, you must be a registered user of [(site_name)]. If you haven\'t already registered, you can  <a href=\"[~5~]\">request an account</a>.</p>\n<div> [!WebLogin? &tpl=`WebLoginSideBar` &loginhomeid=`2`!] </div>','1','4','9','0','0','1','1144904400','1','1144904400','0','0','0','0','0','[*loginName*]','0','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('5','document','text/html','Request an Account','Sign Up for Full Site Privileges','','request-an-account','','1','0','0','0','0','','[[WebSignup? &tpl=`FormSignup` &groups=`Registered Users`]]','1','4','7','0','0','1','1144904400','1','1158320704','0','0','0','0','0','','0','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('6','document','text/html','Contact Us','Contact [(site_name)]','','contact-us','','1','0','0','0','0','','[!eForm? &formid=`ContactForm` &subject=`[+subject+]` &to=`[(emailsender)]` &ccsender=`1` &tpl=`ContactForm` &report=`ContactFormReport` &invalidClass=`invalidValue` &requiredClass=`requiredValue` &cssStyle=`ContactStyles` &gotoid=`46`  !]\n','0','4','5','1','0','1','1144904400','1','1159303922','0','0','0','0','0','Contact us','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('7','document','text/html','404 - Document Not Found','Uh oh ... it\'s a 404! (Page Not Found)','','doc-not-found','','1','0','0','0','0','','<p>Looks like you tried to go somewhere that does not exist... perhaps you <a href=\"\">need to login</a> or you\'d like one of the following pages instead:</p>\n\n[[Wayfinder? &startId=`0` &showDescription=`1`]]\n\n<h3>Want to find it the old fashioned way? Use the site search at the top of this site to find what you seek.</h3>\n\n','1','4','12','0','1','1','1144904400','1','1159301173','0','0','0','0','0','','0','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('8','document','text/html','Search Results','Your Search Results','','search-results','','1','0','0','0','0','','[!AjaxSearch? &showInputForm=`0` &ajaxSearch=`0`!]','0','4','10','0','0','1','1144904400','1','1158613055','0','0','0','0','0','','1','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('9','document','text/html','Mini-Blog HOWTO','How to Start Posting with MODX Mini-Blogs','','article-1126081344','','1','0','0','2','0','','<p>Setting up a mini-blog is relatively simple. Here\'s what you need to do to get started with making new posts:</p>\n<ol>\n<li>Login to the <a href=\"[(site_manager_url)]\">MODX Control Panel</a>.</li>\n<li>Press the plus-sign next to the Blog(2) container resource to see the blog entries posted there.</li>\n<li>To make a new Blog entry, simply right-click the Blog container document and choose the \"Create Resource here\" menu option. To edit an existing blog article, right click the entry and choose the \"Edit Resource\" menu option.</li>\n<!-- splitter -->\n<li>Write or edit the content and press save, making sure the document is published.</li>\n<li>Everything else is automatic; you\'re done!</li>\n</ol>\n{{Comments}}','1','4','0','1','1','-1','1144904400','1','1378084370','0','0','0','0','0','','0','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('11','document','text/xml','RSS Feed','[(site_name)] RSS Feed','','feed.rss','','1','0','0','0','0','','[[Ditto? &parents=`2` &format=`rss` &display=`20` &total=`20` &removeChunk=`Comments`]]','0','0','11','0','0','1','1144904400','1','1160062859','0','0','0','0','0','','0','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('14','document','text/html','Content Management','Ways to manage content','','cms','','1','0','0','15','0','','<h2>Manage your content in the backend</h2>\n<p>The Manager is a skinnable feature-packed tool for admin users. You can add extra users and limit what functions they can access. MODX\'s Manager makes creating content and managing templates and reusable elements easy. Modules can be added to work with other datasets or make management tasks easier.</p>\n<h2>Manage your content in the frontend</h2>\n<p>The QuickEdit bar lets manager users edit content whilst browsing the site. Most content fields and template variables can be edited quickly and easily.</p>\n<h2>Enable web users to add content</h2>\n<p>Custom data entry is easy to code using the MODX API - so you can design forms and collect whatever information you need.</p>','0','4','3','1','1','1','1144904400','1','1378086298','0','0','0','1378086298','1','Manage Content','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('15','document','text/html','MODX Features','MODX Features','','features','','1','0','0','0','1','','[[Wayfinder?startId=`[*id*]` &outerClass=`topnav`]]','1','4','2','1','1','1','1144904400','1','1158452722','0','0','0','1144777367','1','Features','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('16','document','text/html','Ajax','Ajax and Web 2.0 ready','','ajax','','1','1159264800','0','15','0','','<b>Ajax ready out-of-the-box</b>\n<p>MODX empowers users to build engaging sites today, with its pre-integrated <a href=\"http://mootools.net/\" target=\"_blank\">Mootools</a> javascript library.</p>\n\n<p>Check out the Ajax-powered search in this example site. The libraries are also used with QuickEdit, our front-end editing tool.</p>\n\n<p>Smart integration means the scripts are only included in the document head when needed - no unnecessary bloat on simple pages!</p>\n\n<b>Web 2.0 today</b>\n<p>MODX makes child\'s play of building content managed sites with validating, accessible CSS layouts - so web standards compliance is easy. (You can create a site with excessively nested tables too, if you really want to).</p>\n','1','4','1','1','1','1','1144904400','1','1159307504','0','0','0','0','0','Ajax','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('18','document','text/html','Just a pretend, older post','This post should in fact be archived','','article-1128398162','','1','0','0','2','0','','<p>Not so exciting, after all, eh?<br /></p>\n','1','4','2','1','1','-1','1144904400','1','1159306886','0','0','0','0','0','','0','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('22','document','text/html','Menus and Lists','Flexible Menus and Lists','','menus','','1','1159178400','0','15','0','','<h2>Your documents - listed how you want them</h2>\n<p>MODX\'s document data structure has been designed to allow many different routines to redisplay the information in ways that suit your needs, such as a dynamic menu in your template.</p>\n<p>Since the last release of MODX, the community has produced many great snippets - reusable functions that you can call in your content or template. Two of the most widely useful are Ditto and Wayfinder.</p>\n<h2>Wayfinder - the menu builder</h2>\n<p>Allows you to template every part of the menu. On this site, Wayfinder is being used to generate the drop-down menus, but many types of menus and sitemaps are possible.</p>\n<h2>Ditto - the document lister</h2>\n<p>Uses include listing the most recent blog posts, producing a site map, listing related documents (using a TV filter) and generating an RSS feed. You could even write a menu with it. On this site, Ditto is being used for the blog posts list on the Blog page, and the list on the right of some templates.</p>\n<h2>Unlimited Customization</h2>\n<p>If you can\'t quite get your desired effect using templating and the many options of Ditto and Wayfinder, you can write your own routine, or look for other snippets in <a href=\"http://modx.com/extras/\">the MODX repository</a>. MODX\'s fields for Menu Title, summaries, menu position etc can be used via the API to produce anything you can imagine.</p>','1','4','2','1','1','1','1144904400','1','1160148522','0','0','0','0','0','Menus and Lists','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('24','document','text/html','Extendable by design','Extendable by design','','extendable','','1','1159092732','0','15','0','','<p>The MODX community has created many add-ons which can be found in the <a href=\"http://modx.com/extras/\">Repository</a>, from image galleries and e-commerce to smaller utilities.</p>\n\n<h2>Template Variables with Bindings</h2>\n<p>TVs - Template Variables - are powerful extra fields that you can use with your documents. As an example of an advanced template element that returns a different thing dependent on code or data, we created an @BINDING for the name of the Login menu item. This changes the menu name from Login to Logout based on your logged in state. The @BINDING as follows was placed in the default value as:\n<code>@EVAL if ($modx->getLoginUserID()) return \'Logout\'; else return \'Login\';</code></p>\n\n<h2>Using Scriptaculous</h2>\n<p>We used some simple effects to highlight various things on the front/home page to demonstrate how easy it is to create a useful way  to draw attention to things. To see them in action on the home page, click the Integrated Site Search, Related Links or Newest Documents headers.</p>\n\n<h2>Custom Forms</h2>\n<p>To demonstrate how to link to custom forms, we customized the calls to the Webuser Registration system and the Login system.</p>\n\n<h2>And more</h2>\n<p><strong>Rich Text Editor for blog entries.</strong> To make it easier to format blog posts with simple text formatting, we modified the blog to use a custom RTE-enabled Template Variable (TV).</p>\n\n<p><strong>Smart-Summary logic.</strong> When splitting the full blog/news posts you simply insert a \"&lt;!-- splitter -->\" where you want the break to occur. In addition, if that leaves any important tags open, it will try to match them and close them so it doesn\'t mess up your site layout with unclosed OL, UL or DIV tags.</p>','1','4','4','1','1','2','1144904400','1','1159309971','0','0','0','0','0','Extendability','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('32','document','text/html','Design','Site Design','','design','','1','0','0','0','0','','<h3>Credits</h3>\n<p>The default site\'s themes are based off of validating XHTML/CSS designs by <a href=\"http://andreasviklund.com/\">Andreas     Viklund</a>, <a title=\"Complete web design solutions\" href=\"http://ziworks.com/\">ziworks | Web Solutions</a> and <a href=\"http://www.modxhost.com\">MODxHost</a>.</p>','1','4','4','1','1','2','1144904400','1','1160112322','0','0','0','1144912754','1','Design','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('33','document','text/html','Getting Help','Getting Help with MODX','','geting-help','','1','0','0','0','0','','<p>The <a href=\"http://modx.com/\" target=\"_blank\">team behind MODX</a> strives to constantly add to and refine the documentation to help you get up to speed with MODX:</p>\n<ul>\n    <li>For basic instructions on integrating custom templates into MODX, please see the <a target=\"_blank\" href=\"http://rtfm.modx.com/display/Evo1/Designing\">Designer\'s Guide</a>. </li>\n    <li>For an introduction to working in MODX from the content editors perspectve, see the <a target=\"_blank\" href=\"http://rtfm.modx.com/display/Evo1/Content+Editing\">Content Editor\'s Guide</a>. </li>\n    <li>For a detailed overview of the backend &quot;manager&quot; and setting up Users and Groups, please peruse the <a target=\"_blank\" href=\"http://rtfm.modx.com/display/Evo1/Administration\">Administration Guide</a>.</li>\n    <li>For developers, architecture and API documentation can be found in the <a target=\"_blank\" href=\"http://rtfm.modx.com/display/Evo1/Developer%27s+Guide\">Developer\'s Guide</a>.</li>\n    <li>And if someone has installed this site for you, but you\'re curious as to the steps they went through, please see the <a target=\"_blank\" href=\"http://rtfm.modx.com/display/Evo1/Getting+Started\">Getting Started Guide</a>.</li>\n</ul>\n\n<p>And don\'t forget, you can always learn and ask questions at the <a href=\"http://forums.modx.com/\" target=\"_blank\">MODX forums</a>. \n','1','4','3','1','1','2','1144904400','2','1144904400','0','0','0','0','0','Getting Help','0','0','0','0','0','0','0','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('37','document','text/html','[*loginName*]','The page you\'re trying to reach requires a login','','blog-login','','1','0','0','0','0','','<p>In order to add a blog entry, you must be logged in as a Site Admin webuser. Also, commenting on posts requires a login. <a href=\"[~6~]\">Contact the site owner</a> for permissions to create new post, or <a href=\"[~5~]\">create a web user account</a> to automatically receive commenting privileges. If you already have an account, please login below.</p>\n\n[!WebLogin? &tpl=`WebLoginSideBar` &loginhomeid=`3`!]','1','4','8','0','0','1','1144904400','1','1158599931','0','0','0','0','0','','0','0','0','0','0','0','1','1');

REPLACE INTO `{PREFIX}site_content` VALUES ('46','document','text/html','Thank You','','','thank-you','','1','0','0','0','0','','<h3>Thank You!</h3>\n<p>We do appreciate your feedback. Your comments have been submitted to our office and hopefully someone will bother to actually read it. You should also receive a copy of the message in your inbox.</p>\n<p>Please be assured that we will do our best not to ignore you, but if today is a Monday please try again in a few days.</p>\n','1','4','6','1','1','1','1159302141','1','1159302892','0','0','0','1159302182','1','','0','0','0','0','0','0','1','1');


#
# Dumping data for table `site_htmlsnippets`
#


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (4, 'FormSignup', 'For the weblogin signup', 0, 2, 0, '<!-- #declare:separator <hr> --> \r\n<!-- login form section-->\r\n<form id=\"websignupfrm\" method=\"post\" name=\"websignupfrm\" action=\"[+action+]\">\r\n    <fieldset>\r\n        <h3>User Details</h3>\r\n        <p>Items marked by * are required</p>\r\n		<label for=\"su_username\">User name:* <input type=\"text\" name=\"username\" id=\"su_username\" class=\"inputBox\" size=\"20\" maxlength=\"30\" value=\"[+username+]\" /></label>\r\n        <label for=\"fullname\">Full name: <input type=\"text\" name=\"fullname\" id=\"fullname\" class=\"inputBox\" size=\"20\" maxlength=\"100\" value=\"[+fullname+]\" /></label>\r\n		<label for=\"email\">Email address:* <input type=\"text\" name=\"email\" id=\"email\" class=\"inputBox\" size=\"20\" value=\"[+email+]\" /></label>\r\n	</fieldset>\r\n	\r\n	<fieldset>\r\n	    <h3>Password</h3>\r\n	    <label for=\"su_password\">Password:* <input type=\"password\" name=\"password\" id=\"su_password\" class=\"inputBox\" size=\"20\" /></label>\r\n	    <label for=\"confirmpassword\">Confirm password:* <input type=\"password\" name=\"confirmpassword\" id=\"confirmpassword\" class=\"inputBox\" size=\"20\" /></label>\r\n	</fieldset>\r\n	\r\n	<fieldset>\r\n		<h3>Optional Account Profile Info</h3>\r\n		<label for=\"country\">Country:</label>\r\n		<select size=\"1\" name=\"country\" id=\"country\">\r\n			<option value=\"\" selected=\"selected\">&nbsp;</option>\r\n			<option value=\"1\">Afghanistan</option>\r\n			<option value=\"2\">Albania</option>\r\n			<option value=\"3\">Algeria</option>\r\n			<option value=\"4\">American Samoa</option>\r\n			<option value=\"5\">Andorra</option>\r\n			<option value=\"6\">Angola</option>\r\n			<option value=\"7\">Anguilla</option>\r\n			<option value=\"8\">Antarctica</option>\r\n			<option value=\"9\">Antigua and Barbuda</option>\r\n			<option value=\"10\">Argentina</option>\r\n			<option value=\"11\">Armenia</option>\r\n			<option value=\"12\">Aruba</option>\r\n			<option value=\"13\">Australia</option>\r\n			<option value=\"14\">Austria</option>\r\n			<option value=\"15\">Azerbaijan</option>\r\n			<option value=\"16\">Bahamas</option>\r\n			<option value=\"17\">Bahrain</option>\r\n			<option value=\"18\">Bangladesh</option>\r\n			<option value=\"19\">Barbados</option>\r\n			<option value=\"20\">Belarus</option>\r\n			<option value=\"21\">Belgium</option>\r\n			<option value=\"22\">Belize</option>\r\n			<option value=\"23\">Benin</option>\r\n			<option value=\"24\">Bermuda</option>\r\n			<option value=\"25\">Bhutan</option>\r\n			<option value=\"26\">Bolivia</option>\r\n			<option value=\"27\">Bosnia and Herzegowina</option>\r\n			<option value=\"28\">Botswana</option>\r\n			<option value=\"29\">Bouvet Island</option>\r\n			<option value=\"30\">Brazil</option>\r\n			<option value=\"31\">British Indian Ocean Territory</option>\r\n			<option value=\"32\">Brunei Darussalam</option>\r\n			<option value=\"33\">Bulgaria</option>\r\n			<option value=\"34\">Burkina Faso</option>\r\n			<option value=\"35\">Burundi</option>\r\n			<option value=\"36\">Cambodia</option>\r\n			<option value=\"37\">Cameroon</option>\r\n			<option value=\"38\">Canada</option>\r\n			<option value=\"39\">Cape Verde</option>\r\n			<option value=\"40\">Cayman Islands</option>\r\n			<option value=\"41\">Central African Republic</option>\r\n			<option value=\"42\">Chad</option>\r\n			<option value=\"43\">Chile</option>\r\n			<option value=\"44\">China</option>\r\n			<option value=\"45\">Christmas Island</option>\r\n			<option value=\"46\">Cocos (Keeling) Islands</option>\r\n			<option value=\"47\">Colombia</option>\r\n			<option value=\"48\">Comoros</option>\r\n			<option value=\"49\">Congo</option>\r\n			<option value=\"50\">Cook Islands</option>\r\n			<option value=\"51\">Costa Rica</option>\r\n			<option value=\"52\">Cote D&#39;Ivoire</option>\r\n			<option value=\"53\">Croatia</option>\r\n			<option value=\"54\">Cuba</option>\r\n			<option value=\"55\">Cyprus</option>\r\n			<option value=\"56\">Czech Republic</option>\r\n			<option value=\"57\">Denmark</option>\r\n			<option value=\"58\">Djibouti</option>\r\n			<option value=\"59\">Dominica</option>\r\n			<option value=\"60\">Dominican Republic</option>\r\n			<option value=\"61\">East Timor</option>\r\n			<option value=\"62\">Ecuador</option>\r\n			<option value=\"63\">Egypt</option>\r\n			<option value=\"64\">El Salvador</option>\r\n			<option value=\"65\">Equatorial Guinea</option>\r\n			<option value=\"66\">Eritrea</option>\r\n			<option value=\"67\">Estonia</option>\r\n			<option value=\"68\">Ethiopia</option>\r\n			<option value=\"69\">Falkland Islands (Malvinas)</option>\r\n			<option value=\"70\">Faroe Islands</option>\r\n			<option value=\"71\">Fiji</option>\r\n			<option value=\"72\">Finland</option>\r\n			<option value=\"73\">France</option>\r\n			<option value=\"74\">France, Metropolitan</option>\r\n			<option value=\"75\">French Guiana</option>\r\n			<option value=\"76\">French Polynesia</option>\r\n			<option value=\"77\">French Southern Territories</option>\r\n			<option value=\"78\">Gabon</option>\r\n			<option value=\"79\">Gambia</option>\r\n			<option value=\"80\">Georgia</option>\r\n			<option value=\"81\">Germany</option>\r\n			<option value=\"82\">Ghana</option>\r\n			<option value=\"83\">Gibraltar</option>\r\n			<option value=\"84\">Greece</option>\r\n			<option value=\"85\">Greenland</option>\r\n			<option value=\"86\">Grenada</option>\r\n			<option value=\"87\">Guadeloupe</option>\r\n			<option value=\"88\">Guam</option>\r\n			<option value=\"89\">Guatemala</option>\r\n			<option value=\"90\">Guinea</option>\r\n			<option value=\"91\">Guinea-bissau</option>\r\n			<option value=\"92\">Guyana</option>\r\n			<option value=\"93\">Haiti</option>\r\n			<option value=\"94\">Heard and Mc Donald Islands</option>\r\n			<option value=\"95\">Honduras</option>\r\n			<option value=\"96\">Hong Kong</option>\r\n			<option value=\"97\">Hungary</option>\r\n			<option value=\"98\">Iceland</option>\r\n			<option value=\"99\">India</option>\r\n			<option value=\"100\">Indonesia</option>\r\n			<option value=\"101\">Iran (Islamic Republic of)</option>\r\n			<option value=\"102\">Iraq</option>\r\n			<option value=\"103\">Ireland</option>\r\n			<option value=\"104\">Israel</option>\r\n			<option value=\"105\">Italy</option>\r\n			<option value=\"106\">Jamaica</option>\r\n			<option value=\"107\">Japan</option>\r\n			<option value=\"108\">Jordan</option>\r\n			<option value=\"109\">Kazakhstan</option>\r\n			<option value=\"110\">Kenya</option>\r\n			<option value=\"111\">Kiribati</option>\r\n			<option value=\"112\">Korea, Democratic People&#39;s Republic of</option>\r\n			<option value=\"113\">Korea, Republic of</option>\r\n			<option value=\"114\">Kuwait</option>\r\n			<option value=\"115\">Kyrgyzstan</option>\r\n			<option value=\"116\">Lao People&#39;s Democratic Republic</option>\r\n			<option value=\"117\">Latvia</option>\r\n			<option value=\"118\">Lebanon</option>\r\n			<option value=\"119\">Lesotho</option>\r\n			<option value=\"120\">Liberia</option>\r\n			<option value=\"121\">Libyan Arab Jamahiriya</option>\r\n			<option value=\"122\">Liechtenstein</option>\r\n			<option value=\"123\">Lithuania</option>\r\n			<option value=\"124\">Luxembourg</option>\r\n			<option value=\"125\">Macau</option>\r\n			<option value=\"126\">Macedonia, The Former Yugoslav Republic of</option>\r\n			<option value=\"127\">Madagascar</option>\r\n			<option value=\"128\">Malawi</option>\r\n			<option value=\"129\">Malaysia</option>\r\n			<option value=\"130\">Maldives</option>\r\n			<option value=\"131\">Mali</option>\r\n			<option value=\"132\">Malta</option>\r\n			<option value=\"133\">Marshall Islands</option>\r\n			<option value=\"134\">Martinique</option>\r\n			<option value=\"135\">Mauritania</option>\r\n			<option value=\"136\">Mauritius</option>\r\n			<option value=\"137\">Mayotte</option>\r\n			<option value=\"138\">Mexico</option>\r\n			<option value=\"139\">Micronesia, Federated States of</option>\r\n			<option value=\"140\">Moldova, Republic of</option>\r\n			<option value=\"141\">Monaco</option>\r\n			<option value=\"142\">Mongolia</option>\r\n			<option value=\"143\">Montserrat</option>\r\n			<option value=\"144\">Morocco</option>\r\n			<option value=\"145\">Mozambique</option>\r\n			<option value=\"146\">Myanmar</option>\r\n			<option value=\"147\">Namibia</option>\r\n			<option value=\"148\">Nauru</option>\r\n			<option value=\"149\">Nepal</option>\r\n			<option value=\"150\">Netherlands</option>\r\n			<option value=\"151\">Netherlands Antilles</option>\r\n			<option value=\"152\">New Caledonia</option>\r\n			<option value=\"153\">New Zealand</option>\r\n			<option value=\"154\">Nicaragua</option>\r\n			<option value=\"155\">Niger</option>\r\n			<option value=\"156\">Nigeria</option>\r\n			<option value=\"157\">Niue</option>\r\n			<option value=\"158\">Norfolk Island</option>\r\n			<option value=\"159\">Northern Mariana Islands</option>\r\n			<option value=\"160\">Norway</option>\r\n			<option value=\"161\">Oman</option>\r\n			<option value=\"162\">Pakistan</option>\r\n			<option value=\"163\">Palau</option>\r\n			<option value=\"164\">Panama</option>\r\n			<option value=\"165\">Papua New Guinea</option>\r\n			<option value=\"166\">Paraguay</option>\r\n			<option value=\"167\">Peru</option>\r\n			<option value=\"168\">Philippines</option>\r\n			<option value=\"169\">Pitcairn</option>\r\n			<option value=\"170\">Poland</option>\r\n			<option value=\"171\">Portugal</option>\r\n			<option value=\"172\">Puerto Rico</option>\r\n			<option value=\"173\">Qatar</option>\r\n			<option value=\"174\">Reunion</option>\r\n			<option value=\"175\">Romania</option>\r\n			<option value=\"176\">Russian Federation</option>\r\n			<option value=\"177\">Rwanda</option>\r\n			<option value=\"178\">Saint Kitts and Nevis</option>\r\n			<option value=\"179\">Saint Lucia</option>\r\n			<option value=\"180\">Saint Vincent and the Grenadines</option>\r\n			<option value=\"181\">Samoa</option>\r\n			<option value=\"182\">San Marino</option>\r\n			<option value=\"183\">Sao Tome and Principe</option>\r\n			<option value=\"184\">Saudi Arabia</option>\r\n			<option value=\"185\">Senegal</option>\r\n			<option value=\"186\">Seychelles</option>\r\n			<option value=\"187\">Sierra Leone</option>\r\n			<option value=\"188\">Singapore</option>\r\n			<option value=\"189\">Slovakia (Slovak Republic)</option>\r\n			<option value=\"190\">Slovenia</option>\r\n			<option value=\"191\">Solomon Islands</option>\r\n			<option value=\"192\">Somalia</option>\r\n			<option value=\"193\">South Africa</option>\r\n			<option value=\"194\">South Georgia and the South Sandwich Islands</option>\r\n			<option value=\"195\">Spain</option>\r\n			<option value=\"196\">Sri Lanka</option>\r\n			<option value=\"197\">St. Helena</option>\r\n			<option value=\"198\">St. Pierre and Miquelon</option>\r\n			<option value=\"199\">Sudan</option>\r\n			<option value=\"200\">Suriname</option>\r\n			<option value=\"201\">Svalbard and Jan Mayen Islands</option>\r\n			<option value=\"202\">Swaziland</option>\r\n			<option value=\"203\">Sweden</option>\r\n			<option value=\"204\">Switzerland</option>\r\n			<option value=\"205\">Syrian Arab Republic</option>\r\n			<option value=\"206\">Taiwan</option>\r\n			<option value=\"207\">Tajikistan</option>\r\n			<option value=\"208\">Tanzania, United Republic of</option>\r\n			<option value=\"209\">Thailand</option>\r\n			<option value=\"210\">Togo</option>\r\n			<option value=\"211\">Tokelau</option>\r\n			<option value=\"212\">Tonga</option>\r\n			<option value=\"213\">Trinidad and Tobago</option>\r\n			<option value=\"214\">Tunisia</option>\r\n			<option value=\"215\">Turkey</option>\r\n			<option value=\"216\">Turkmenistan</option>\r\n			<option value=\"217\">Turks and Caicos Islands</option>\r\n			<option value=\"218\">Tuvalu</option>\r\n			<option value=\"219\">Uganda</option>\r\n			<option value=\"220\">Ukraine</option>\r\n			<option value=\"221\">United Arab Emirates</option>\r\n			<option value=\"222\">United Kingdom</option>\r\n			<option value=\"223\">United States</option>\r\n			<option value=\"224\">United States Minor Outlying Islands</option>\r\n			<option value=\"225\">Uruguay</option>\r\n			<option value=\"226\">Uzbekistan</option>\r\n			<option value=\"227\">Vanuatu</option>\r\n			<option value=\"228\">Vatican City State (Holy See)</option>\r\n			<option value=\"229\">Venezuela</option>\r\n			<option value=\"230\">Viet Nam</option>\r\n			<option value=\"231\">Virgin Islands (British)</option>\r\n			<option value=\"232\">Virgin Islands (U.S.)</option>\r\n			<option value=\"233\">Wallis and Futuna Islands</option>\r\n			<option value=\"234\">Western Sahara</option>\r\n			<option value=\"235\">Yemen</option>\r\n			<option value=\"236\">Yugoslavia</option>\r\n			<option value=\"237\">Zaire</option>\r\n			<option value=\"238\">Zambia</option>\r\n			<option value=\"239\">Zimbabwe</option>\r\n			</select>\r\n        </fieldset>\r\n        \r\n        <fieldset>\r\n            <h3>Bot-Patrol</h3>\r\n            <p>Enter the word/number combination shown in the image below.</p>\r\n            <p><a href=\"[+action+]\"><img align=\"top\" src=\"[(site_manager_url)]includes/veriword.php\" width=\"148\" height=\"60\" alt=\"If you have trouble reading the code, click on the code itself to generate a new random code.\" style=\"border: 1px solid #039\" /></a></p>\r\n        <label>Form code:* \r\n            <input type=\"text\" name=\"formcode\" class=\"inputBox\" size=\"20\" /></label>\r\n            </fieldset>\r\n        \r\n        <fieldset>\r\n            <input type=\"submit\" value=\"Submit\" name=\"cmdwebsignup\" />\r\n	</fieldset>\r\n</form>\r\n\r\n<script language=\"javascript\" type=\"text/javascript\"> \r\n	var id = \"[+country+]\";\r\n	var f = document.websignupfrm;\r\n	var i = parseInt(id);	\r\n	if (!isNaN(i)) f.country.options[i].selected = true;\r\n</script>\r\n<hr>\r\n<!-- notification section -->\r\n<p class=\"message\">Signup completed successfully!<br />\r\nYour account was created. A copy of your signup information was sent to your email address.</p>\r\n', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (6, 'nl_sidebar', 'Default Template TPL for Ditto', 0, 1, 0, '<strong><a href="[~[+id+]~]" title="[+title+]">[+title+]</a></strong><br />\r\n[+longtitle+]<br /><br />', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (8, 'ditto_blog', 'Blog Template', 0, 1, 0, '<div class="ditto_summaryPost">\r\n\  <h3><a href="[~[+id+]~]" title="[+title+]">[+title+]</a></h3>\r\n  <div class="ditto_info" >By <strong>[+author+]</strong> on [+date+]. <a  href="[~[+id+]~]#commentsAnchor">Comments\r\n  ([!Jot?&docid=`[+id+]`&action=`count-comments`!])</a></div><div class="ditto_tags">Tags: [+tagLinks+]</div>\r\n  [+summary+]\r\n  <p class="ditto_link">[+link+]</p>\r\n</div>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (9, 'footer', 'Site Template Footer', 0, 1, 0, '[(site_name)] is powered by <a href="http://modx.com/" title="Powered by MODX, Do more with less.">MODX CMS</a> |\r\n      <span id="andreas">Design by <a href="http://andreasviklund.com/">Andreas Viklund</a></span>\r\n<span id="zi" style="display: none">Designed by <a href="http://ziworks.com/" target="_blank" title="E-Business &amp; webdesign solutions">ziworks</a></span>\r\n\r\n<!-- the modx icon -->\r\n\r\n<div id="modxicon"><h6><a href="http://modx.com" title="MODX - The XHTML, CSS and Ajax CMS and PHP Application Framework" id="modxicon32">MODX - The XHTML, CSS and Ajax CMS and PHP Application Framework</a></h6></div>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (10, 'meta', 'Site Template Meta', 0, 1, 0, '<p><a href="http://validator.w3.org/check/referer" title="This page validates as XHTML 1.0 Transitional">Valid <abbr title="eXtensible HyperText Markup Language">XHTML</abbr></a></p>                	<p><a href="http://jigsaw.w3.org/css-validator/check/referer" title="This page uses valid Cascading Stylesheets" rel="external">Valid <abbr title="W3C Cascading Stylesheets">css</abbr></a></p>				    <p><a href="http://modx.com/" title="Powered by MODX, Do more with less.">MOD<strong>x</strong></a></p>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (11, 'mh.InnerRowTpl', 'Inner row template for MODXHost top menu', 0, 1, 0, '<li[+wf.classes+]><a href="[+wf.link+]" title="[+wf.title+]">[+wf.linktext+]</a>[+wf.wrapper+]</li>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (12, 'mh.InnerTpl', 'Inner nesting template for MODXHost top menu', 0, 1, 0, '<ul style="display:none">\r\n  [+wf.wrapper+]\r\n</ul>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (13, 'mh.OuterTpl', 'Outer nesting template for MODXHost top menu', 0, 1, 0, '  <ul id="myajaxmenu">\r\n    [+wf.wrapper+]\r\n  </ul>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (14, 'mh.RowTpl', 'Row template for MODXHost top menu', 0, 1, 0, '<li class="category [+wf.classnames+]"><a href="[+wf.link+]" title="[+wf.title+]">[+wf.linktext+]</a>[+wf.wrapper+]</li>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (15, 'Comments', 'Comments (Jot) showing beneath a blog entry.', 0, 1, 0, '<div id="commentsAnchor">\r\n[!Jot? &customfields=`name,email` &subscribe=`1` &pagination=`4` &badwords=`dotNet` &canmoderate=`Site Admins` !]\r\n</div>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (16, 'ContactForm', '', 0, 1, 0, '<p class="error">[+validationmessage+]</p>\r\n\r\n<form method="post" action="[~[*id*]~]" id="EmailForm" name="EmailForm">\r\n\r\n	<fieldset>\r\n		<h3> Contact Form</h3>\r\n\r\n		<input name="formid" type="hidden" value="ContactForm" />\r\n\r\n		<label for="cfName">Your name:\r\n		<input name="name" id="cfName" class="text" type="text" eform="Your Name::1:" /> </label>\r\n\r\n		<label for="cfEmail">Your Email Address:\r\n		<input name="email" id="cfEmail" class="text" type="text" eform="Email Address:email:1" /> </label>\r\n\r\n		<label for="cfRegarding">Regarding:</label>\r\n		<select name="subject" id="cfRegarding" eform="Form Subject::1">\r\n			<option value="General Inquiries">General Inquiries</option>\r\n			<option value="Press">Press or Interview Request</option>\r\n			<option value="Partnering">Partnering Opportunities</option>\r\n		</select>\r\n\r\n		<label for="cfMessage">Message: \r\n		<textarea name="message" id="cfMessage" rows="4" cols="20" eform="Message:textarea:1"></textarea>\r\n		</label>\r\n\r\n		<label>&nbsp;</label><input type="submit" name="contact" id="cfContact" class="button" value="Send This Message" />\r\n\r\n	</fieldset>\r\n\r\n</form>\r\n', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (17, 'ContactFormReport', '', 0, 1, 0, '<p>This is a response sent by <b>[+name+]</b> using the feedback form on the website. The details of the message follow below:</p>\r\n\r\n\r\n<p>Name: [+name+]</p>\r\n<p>Email: [+email+]</p>\r\n<p>Regarding: [+subject+]</p>\r\n<p>comments:<br />[+message+]</p>\r\n\r\n<p>You can use this link to reply: <a href="mailto:[+email+]?subject=RE: [+subject+]">[+email+]</a></p>\r\n', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (18, 'reflect_month_tpl', 'For the yearly archive. Use with Ditto.', 0, 1, 0, '<a href="[+url+]" title="[+month+] [+year+]" class="reflect_month_link">[+month+] [+year+]</a>', 0);


REPLACE INTO `{PREFIX}site_htmlsnippets` VALUES (19, 'ContactStyles', 'Styles for form validation', 0, 1, 0, '<style type="text/css">\r\ndiv.errors{ color:#F00; }\r\n#EmailForm .invalidValue{ background: #FFDFDF; border:1px solid #F00; }\r\n#EmailForm .requiredValue{ background: #FFFFDF; border:1px solid #F00; }\r\n</style>', 0);



#
# Dumping data for table `site_keywords`
#


REPLACE INTO `{PREFIX}site_keywords` VALUES ('1','MODX');


REPLACE INTO `{PREFIX}site_keywords` VALUES ('2','content management system');


REPLACE INTO `{PREFIX}site_keywords` VALUES ('3','Front End Editing');


REPLACE INTO `{PREFIX}site_keywords` VALUES ('4','login');


#
# Dumping data for table `MODX2352_site_tmplvar_contentvalues`
#


REPLACE INTO `{PREFIX}site_tmplvar_contentvalues` VALUES ('1','3','9','demo miniblog howto tutorial posting');


REPLACE INTO `{PREFIX}site_tmplvar_contentvalues` VALUES ('2','3','18','demo older posting');


#
# Dumping data for table `system_settings`
#


REPLACE INTO `{PREFIX}system_settings` VALUES('error_page', '7');


REPLACE INTO `{PREFIX}system_settings` VALUES('unauthorized_page', '4');


#
# Dumping data for table `web_groups`
#


REPLACE INTO `{PREFIX}web_groups` VALUES ('1','1','1');


#
# Dumping data for table `web_user_attributes`
#


REPLACE INTO `{PREFIX}web_user_attributes` VALUES ('1','1','Site Admin','0','you@example.com','','','0','0','0','25','1129049624','1129063123','0','f426f3209310abfddf2ee00e929774b4','0','0','','','','','','','','');


#
# Dumping data for table `web_users`
#


REPLACE INTO `{PREFIX}web_users` VALUES ('1','siteadmin','5f4dcc3b5aa765d61d8327deb882cf99','');


#
# Dumping data for table `webgroup_access`
#


REPLACE INTO `{PREFIX}webgroup_access` VALUES ('1','1','1');


#
# Dumping data for table `webgroup_names`
#


REPLACE INTO `{PREFIX}webgroup_names` VALUES ('1','Site Admins');


REPLACE INTO `{PREFIX}webgroup_names` VALUES ('2','Registered Users');



#
# Table structure for table `jot_content`
#


CREATE TABLE IF NOT EXISTS `{PREFIX}jot_content` (`id` int(10) NOT NULL auto_increment, `title` varchar(255) default NULL, `tagid` varchar(50) default NULL, `published` int(1) NOT NULL default '0', `uparent` int(10) NOT NULL default '0', `parent` int(10) NOT NULL default '0', `flags` varchar(25) default NULL, `secip` varchar(32) default NULL, `sechash` varchar(32) default NULL, `content` mediumtext, `customfields` mediumtext, `mode` int(1) NOT NULL default '1', `createdby` int(10) NOT NULL default '0', `createdon` int(20) NOT NULL default '0', `editedby` int(10) NOT NULL default '0', `editedon` int(20) NOT NULL default '0', `deleted` int(1) NOT NULL default '0', `deletedon` int(20) NOT NULL default '0', `deletedby` int(10) NOT NULL default '0', `publishedon` int(20) NOT NULL default '0', `publishedby` int(10) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `parent` (`parent`), KEY `secip` (`secip`), KEY `tagidx` (`tagid`), KEY `uparent` (`uparent`)) ENGINE=MyISAM;


#
# Dumping data for table `jot_content`
#


REPLACE INTO `{PREFIX}jot_content` VALUES ('9','The first comment','','1','9','0','','87.211.130.14','edb75dab198ff302efbf2f60e548c0b3','This is the first comment.','<custom><name></name><email></email></custom>','0','0','1160420310','0','0','0','0','0','0','0');


REPLACE INTO `{PREFIX}jot_content` VALUES ('10','Second comment','','1','9','0','','87.211.130.14','edb75dab198ff302efbf2f60e548c0b3','This is the second comment and uses an alternate row color. I also supplied a name, but i\'m not logged in.','<custom><name>Armand</name><email></email></custom>','0','0','1160420453','0','0','0','0','0','0','0');


REPLACE INTO `{PREFIX}jot_content` VALUES ('11','No abuse','','1','9','0','','87.211.130.14','edb75dab198ff302efbf2f60e548c0b3','Notice that I can\'t abuse <b>html</b>, ,  or [+placeholder+] tags.\r\n\r\nA new line also doesn\'t come unnoticed.','<custom><name>Armand</name><email></email></custom>','0','0','1160420681','0','0','0','0','0','0','0');


REPLACE INTO `{PREFIX}jot_content` VALUES ('12','Posting when logged in','','1','9','0','','87.211.130.14','58fade927c1df50ba6131f2b0e53c120','When you are logged in your own posts have a special color so you can easily spot them from the comment view. \r\n\r\nThe form also does not display any guest fields when logged in.','<custom></custom>','0','-1','1160421310','0','0','0','0','0','0','0');


REPLACE INTO `{PREFIX}jot_content` VALUES ('13','Managers','','1','9','0','','87.211.130.14','91e230cf219e3ade10f32d6a41d0bd4d','Comments posted when only logged in as a manager user will use your manager name.\r\n\r\nModerators options are always shown when you are logged in as manager user.','<custom></custom>','0','1','1160421487','0','0','0','0','0','0','0');


REPLACE INTO `{PREFIX}jot_content` VALUES ('14','Moderation','','1','9','0','','87.211.130.14','58fade927c1df50ba6131f2b0e53c120','In this setup the Site Admins group is defined as being the moderator for this particular comment view. These users will have extra moderation options \r\n\r\nManager users, Moderators or Trusted users can post bad words like: dotNet.','<custom></custom>','0','-1','1160422081','0','0','0','0','0','0','0');


REPLACE INTO `{PREFIX}jot_content` VALUES ('15','I\'m untrusted','','0','9','0','','87.211.130.14','edb75dab198ff302efbf2f60e548c0b3','Untrusted users however can NOT post bad words like: dotNet. When they do the posts will be unpublished.','<custom><name></name><email></email></custom>','0','0','1160422167','0','0','0','0','0','0','0');


#
# Table structure for table `jot_subscriptions`
#


CREATE TABLE IF NOT EXISTS `{PREFIX}jot_subscriptions` (`id` mediumint(10) NOT NULL auto_increment, `uparent` mediumint(10) NOT NULL default '0', `tagid` varchar(50) NOT NULL default '', `userid` mediumint(10) NOT NULL default '0', PRIMARY KEY  (`id`), KEY `uparent` (`uparent`), KEY `tagid` (`tagid`), KEY `userid` (`userid`)) ENGINE=MyISAM;

