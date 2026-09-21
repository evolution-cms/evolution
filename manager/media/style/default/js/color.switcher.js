//Evolution color switcher
jQuery(document).ready(function($) {
    //appear the evo colors switcher sidebar
    $('.evocp-box .evocp-icon').on('click', function() {
        $('.evocp-box').toggleClass('appear-it');
    });
    $('.evocp-box').attr('unselectable', 'on')
        .css('user-select', 'none')
        .on('selectstart', false);

    //vars//
    var bgmcolor;
    var color;
    var menuColor;
    var menuHColor;
    var alinkcolor;

    // Get text color
    var my_evo_color = localStorage.getItem('my_evo_color');
    if (my_evo_color) {
        color = my_evo_color;
        setColor(color);
    }
    // Get alink color    
    var my_evo_alinkcolor = localStorage.getItem('my_evo_alinkcolor');
    if (my_evo_alinkcolor) {
        alinkcolor = my_evo_alinkcolor;
        setalinkColor(alinkcolor);
    }
    // Get background menu color
    var my_evo_bgmcolor = localStorage.getItem('my_evo_bgmcolor');
    if (my_evo_bgmcolor) {
        bgmcolor = my_evo_bgmcolor;
        setBgmColor(bgmcolor);
    }
    // Get menu link color
    var my_evo_menuColor = localStorage.getItem('my_evo_menuColor');
    if (my_evo_menuColor) {
        menuColor = my_evo_menuColor;
        setMenuColor(menuColor);
    }
    // Get menu link hover color
    var my_evo_menuHColor = localStorage.getItem('my_evo_menuHColor');
    if (my_evo_menuHColor) {
        menuHColor = my_evo_menuHColor;
        setMenuHColor(menuHColor);
    }
    
    //Menu background part

    //change elements colors
    function setBgmColor(bgmcolor) {
        $('[data-evocp="bgmColor"]').css('backgroundColor', bgmcolor);
        $('#mainMenu li.item > a').mouseover(function() {
            $(this).css('backgroundColor', bgmcolor);
            $(this).css('color', '#fff');
        }).mouseout(function() {
            $(this).css('backgroundColor', '#FFF');
            $(this).css('color', '#444');
        });
    }
    //store the color value in a variable
    $('.evocp-bgmcolor').each(function(i) {
        $('.evocp-bgmcolor').eq(i).css('backgroundColor', $(this).text());
        $('.evocp-bgmcolor').eq(i).on('click', function() {
            bgmcolor = $(this).css('backgroundColor');
            // Save the color in local storage
            localStorage.setItem('my_evo_bgmcolor', bgmcolor);
          $(this).addClass('colorSelected').siblings().removeClass('colorSelected');
        });
    });
    //Color Picker colors
    $('.bgmPicker .sp-top-inner').on('click', function() {
    bgmcolor = $('.bgmcolors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_bgmcolor', bgmcolor);
        setBgmColor(bgmcolor);        
    });
    $('.bgmPicker .sp-input').on('keydown', function() {
    bgmcolor = $('.bgmcolors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_bgmcolor', bgmcolor);
        setBgmColor(bgmcolor);        
    });
    //apply colors
    $('*').on('click', function() {
        setBgmColor(bgmcolor);
    });
    
    /////menu link Color part

    //change elements colors
    function setMenuColor(menucolor) {
        $('[data-evocp="menuColor"]').css('color', menuColor);
        $("#mainMenu:not(.show) .nav > li > a").css('color', menuColor);
        $("#mainMenu .nav .label_searchid").css('color', menuColor);
        $("#mainMenu.show .nav > li > a").css('color', '#444!important');
    }
    //store the color value in a variable
    $('.evocp-menuColor').each(function(i) {
        $('.evocp-menuColor').eq(i).css('backgroundColor', $(this).text());
        $('.evocp-menuColor').eq(i).on('click', function() {
            menuColor = $(this).css('backgroundColor');
            // Save the color in local storage
            localStorage.setItem('my_evo_menuColor', menuColor);
            $(this).addClass('colorSelected').siblings().removeClass('colorSelected');
        });
    });

    //Color Picker colors
    $('.menucolorPicker .sp-top-inner').on('click', function() {
    menuColor = $('.menuColors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_menuColor', menuColor);
        setMenuColor(menuColor);
    });
    $('.menucolorPicker .sp-input').on('keydown', function() {
    menuColor = $('.menuColors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_menuColor', menuColor);
        setMenuColor(menuColor);
    });
    //apply colors
    $('*').on('click', function() {
        setMenuColor(menuColor);
    });

    /////menu link Hover Color

    //change elements colors
    function setMenuHColor(menuHcolor) {
        $('[data-evocp="menuHColor"]').css('color', menuHColor);
        $('#mainMenu .nav > li:not(.active) > a.hover').css('color', menuHColor);
        $('[data-evocp="menuColor"].hover').css('color', menuHColor);

        //$('#evo-tab-home.tab.selected').css('backgroundColor', bgcolor);

        $('#mainMenu .nav > li:not(.active) > a').mouseover(function() {
            $(this).css('color', menuHColor);
        }).mouseout(function() {
            $(this).css('color', menuColor);
        });
        $('#mainMenu .nav .label_searchid').mouseover(function() {
            $(this).css('color', menuHColor);
        }).mouseout(function() {
            $(this).css('color', menuColor);
        });
        $('#mainMenu .nav > li.active > a').css('color', menuHColor);
        //#mainMenu.show .nav > li.dropdown.hover > a 
        //$('#bgPicker').val('bgcolor');
    }
    //store the color value in a variable
    $('.evocp-menuHColor').each(function(i) {
        $('.evocp-menuHColor').eq(i).css('backgroundColor', $(this).text());
        $('.evocp-menuHColor').eq(i).on('click', function() {
            menuHColor = $(this).css('backgroundColor');
            // Save the color in local storage
            localStorage.setItem('my_evo_menuHColor', menuHColor);
            $(this).addClass('colorSelected').siblings().removeClass('colorSelected');
        });
    });

    //Color Picker colors
    $('.menuHcolorPicker .sp-top-inner').on('click', function() {
    menuHColor = $('.menuHColors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_menuHColor', menuHColor);
        setMenuHColor(menuHColor);
    });
    $('.menuHcolorPicker .sp-input').on('keydown', function() {
    menuHColor = $('.menuHColors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_menuHColor', menuHColor);
        setMenuHColor(menuHColor);
    });
    //apply colors
    $('*').on('click', function() {
        setMenuHColor(menuHColor);
    });

    ///// Body Links color
    //change elements colors
    function setalinkColor(alinkcolor) {
         $('[data-evocp="alinkcolor"]').css('color', alinkcolor);
        //need fix and move to dedicated tree panel
      // $('body:not(.dark) #treeRoot a:not(.deleted):not(.unpublished):not(.hidemenu) .title').css('color', alinkcolor);
      //  $('body #treeRoot a:not(.deleted):not(.unpublished):not(.hidemenu) .title').css('color', alinkcolor);
        $('.container-body a:not(.btn)').css('color', alinkcolor);
    }
    //store the color value in a variable
    $('.evocp-alinkcolor').each(function(i) {
        $('.evocp-alinkcolor').eq(i).css('backgroundColor', $(this).text());
        $('.evocp-alinkcolor').eq(i).on('click', function() {
            alinkcolor = $(this).css('backgroundColor');
            window.main.location.reload();
            // Save the color in local storage
            localStorage.setItem('my_evo_alinkcolor', alinkcolor);
            $(this).addClass('colorSelected').siblings().removeClass('colorSelected');
        });
    });
    //Color Picker colors
    $('.linkcolorPicker .sp-top-inner').on('click', function() {
    alinkcolor = $('.alinkcolors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_alinkcolor', alinkcolor);
        setalinkColor(alinkcolor);
        window.main.location.reload();
    });
    $('.linkcolorPicker .sp-input').on('keydown', function() {
    alinkcolor = $('.alinkcolors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_alinkcolor', alinkcolor);
        setalinkColor(alinkcolor);
        window.main.location.reload();
    });
    //apply colors
    $('*').on('click', function() {
        setalinkColor(alinkcolor);
    });

    /////Body text color
    //change elements colors
    function setColor(color) {
        $('[data-evocp="color"]').css('color', color);
        $('[data-evocp="borderColor"]').css('border-color', color);
        $('h1 .fa:not(.help)').css('color', color); 
   }
    //store the color value in a variable
    $('.evocp-color').each(function(i) {
        $('.evocp-color').eq(i).css('backgroundColor', $(this).text());
        $('.evocp-color').eq(i).on('click', function() {
            color = $(this).css('backgroundColor');
            window.main.location.reload();
            // Save the color in local storage
            localStorage.setItem('my_evo_color', color);
            $(this).addClass('colorSelected').siblings().removeClass('colorSelected');
            //alert("Text color is " + color);
            
        });
    });

    //custom colors
    $('.textcolorPicker .sp-top-inner').on('click', function() {
    color = $('.cpcolors .sp-preview-inner').css("background-color");
        // Save the color in local storage
        localStorage.setItem('my_evo_color', color);
        setColor(color);
        window.main.location.reload();
    });

    //apply colors
    $('*').on('click', function() {
        setColor(color);
    });


////close document ready
});
//clear Local Storage and reload only main frame
function cleanLocalStorageReloadMain(keys) {
    keys = keys.split(',');
    for (var i = 0; i < keys.length; i++) {
        delete localStorage[keys[i]];
    }
    window.main.location.reload();
}
//clear Local Storage and reload all frames
function cleanLocalStorageReloadAll(keys) {
    keys = keys.split(',');
    for (var i = 0; i < keys.length; i++) {
        delete localStorage[keys[i]];
    }
    location.reload();
}
//toggle chevron icon
jQuery(document).on('click', '.panel-heading h3', function(e) {
    var $this = $(this);
    icon = $(this).find("i.togglearrow");
    icon.toggleClass("fa-chevron-right fa-chevron-down")

})
