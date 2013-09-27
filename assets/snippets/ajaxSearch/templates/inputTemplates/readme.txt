/**
 * AjaxSearch
 *
 * Ajax and non-Ajax search that supports results highlighting
 *
 * @version     1.9.2
 * @author       Coroico <coroico@wangba.fr>
 * @date         05/12/2010
 * -----------------------------------------------------------------------------
/**

Input templates

To set an input form you could use the following form elements:
•	a set of check boxes to select the value of the advanced Search parameter (name=advSearch)
•	an input fied (name=search), to catch the search terms you search
•	or a multiple input list to select the search terms among a list of terms (name=search)

To demonstrate these possibilities, several demos exist on the demo site. 
Each demos display the results on the same page under the input form with the non-ajax mode (&ajaxSearch=`0`)
Each demo use the input.tpl.html templates provided in this floder
•	Input 1 : a simple search with a simple input field
•	Input 2 : a simple search with a multiple input list
•	Input 3 : an advanced search. An input field with the selection of the advanced Search parameter

You could mix the advanced search parameter with the input list (2 & 3)

With an input field, when the liveSearch mode is set, the submit button is not displayed. A search occurs after each character typed.

 
