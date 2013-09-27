
==== images folder

This folder contains some png files used to style AjaxSearch

1/ to style the previous / next / show more buttons :

- asnext.png, asprev : right and left arrows to style the next and buttons of the pagingType 1
- asnext.png could also be use to style the show more 10 results button (pagingType 2)

Drop these png files inside the image folder of your css folder

e.g:

    .paging1Prev{ margin:0; padding:8px; background:url(images/asprev.png) 0 center no-repeat;}
    .paging1Next{ margin:0; padding:8px;background:url(images/asnext.png) 0 center no-repeat;}

    .paging2More{ margin:10px 10px; padding:8px;background:url(images/asnext.png) 0 center no-repeat;}
    
    
2/ to style the ajax mode:

By default, used by the js files (mootools or JQuery) to style ajax mode with white background:

- cross.png could be used to style the close indicator of the ajax mode
- indicator.white.png to style the ajax load indicator

To style the ajax mode on black background, change the header of the js files and uses:

- close.png could be used to style the close indicator of the ajax mode
- indicator.black.png to style the ajax load indicator

    
