<div class="stepcontainer">
      <ul class="progressbar">
          <li class="visited">[%choose_language%]</li>
          <li class="visited">[%installation_mode%]</li>
          <li class="active">[%optional_items%]</li>
          <li>[%preinstall_validation%]</li>
          <li>[%install_results%]</li>
  </ul>
  <div class="clearleft"></div>
</div>
<form name="install" id="install_form" action="index.php?action=summary" method="post">
    <div>
        <input type="hidden" value="[+install_language+]" name="language" />
        <input type="hidden" value="[+manager_language+]" name="managerlanguage" />
        <input type="hidden" value="[+installMode+]" name="installmode" />
        <input type="hidden" value="[+database_name+]" name="database_name" />
        <input type="hidden" value="[+tableprefix+]" name="tableprefix" />
        <input type="hidden" value="[+database_collation+]" name="database_collation" />
        <input type="hidden" value="[+database_connection_charset+]" name="database_connection_charset" />
        <input type="hidden" value="[+database_connection_method+]" name="database_connection_method" />
        <input type="hidden" value="[+databasehost+]" name="databasehost" />
        <input type="hidden" value="[+cmsadmin+]" name="cmsadmin" />
        <input type="hidden" value="[+cmsadminemail+]" name="cmsadminemail" />
        <input type="hidden" value="[+cmspassword+]" name="cmspassword" />
        <input type="hidden" value="[+cmspasswordconfirm+]" name="cmspasswordconfirm" />
        <input type="hidden" value="1" name="options_selected" />
    </div>

    <h2>[%optional_items%]</h2>
    <p>[%optional_items_note%]</p>
    <img src="img/sample_site.png" class="options" alt="Sample Data" />
    
        <h3>[%sample_web_site%]</h3>
        <p><input type="checkbox" name="installdata" id="installdata_field" value="1" [+checked+] />&nbsp;<label for="installdata_field">[%install_overwrite%] <span class="comname">[%sample_web_site%]</span></label></p>
        <p><em>&nbsp;[%sample_web_site_note%]</em></p>
        <hr />
            
            <h4>[%checkbox_select_options%]</h4>
            <p class="actions">
            <a class="toggle_check_all" href="#">[%all%]</a>
            <a class="toggle_check_none" href="#">[%none%]</a>
            <a class="toggle_check_toggle" href="#">[%toggle%]</a>
            </p>
            <br class="clear" />
    <div id="installChoices">
        <div class="templates">[+templates+]</div>
        <div class="tvs">[+tvs+]</div>
        <div class="chunks">[+chunks+]</div>
        <div class="modules">[+modules+]</div>
        <div class="plugins">[+plugins+]</div>
        <div class="snippets">[+snippets+]</div>
    </div>
    <p class="buttonlinks">
    <a href="javascript:document.getElementById('install_form').action='index.php?action=[+action+]';document.getElementById('install_form').submit();" class="prev" title="[%btnback_value%]"><span>[%btnback_value%]</span></a>
    <a href="javascript:document.getElementById('install_form').submit();" title="[%btnnext_value%]"><span>[%btnnext_value%]</span></a>
    </p>

</form>
<script type="text/javascript">
    (function($){
        $('#installChoices').find('h3').each(function(){
         $(this).append($("<span/>").addClass("actions").html($('p.actions').html()));
        });  
        $('.actions').on('click','a', function(e){
            e.preventDefault();
            var a = $(this);
            var i = $('input:checkbox.toggle:not(:disabled)');
            var inCh = $('#installChoices').find(a.parent());
            if (inCh.length) i = inCh.closest("div").find(i);
            switch (true) {
                case a.is('.toggle_check_all'):     i.prop('checked', true); break;
                case a.is('.toggle_check_none'):    i.prop('checked', false); break;
                case a.is('.toggle_check_toggle'):  i.prop('checked', function(){ return !$(this).prop('checked');}); break;
            }
        });
        $('#installdata_field').click(function(){
            handleSampleDataCheckbox();
        });
        var handleSampleDataCheckbox = function(){
            demo = $('#installdata_field').prop('checked');
            $('input:checkbox.toggle.demo').each(function(ix, el){
                if(demo) {
                    $(this).prop('checked', true).prop('disabled', true);
                } else {
                    $(this).prop('disabled', false);
                }
            });
        };
        
        // handle state of demo content checkbox on page load
        handleSampleDataCheckbox();
    })(jQuery);
    
</script>