<div class="stepcontainer">
      <ul class="progressbar">
          <li class="visited">[%choose_language%]</li>
          <li class="active">[%installation_mode%]</li>
          <li>[%optional_items%]</li>
          <li>[%preinstall_validation%]</li>
          <li>[%install_results%]</li>
  </ul>
  <div class="clearleft"></div>
</div>
<form name="install" id="install_form" action="index.php?action=connection" method="post">
	<h2>[%welcome_message_welcome%]</h2>
	<p>[%welcome_message_text%] [%welcome_message_start%]</p>
	<div>
		<input type="hidden" value="[+install_language+]" name="language" />
	</div>
	<h2 style="margin:1em 0">[%installation_mode%]</h2>
	<div style="overflow:hidden;[+displayNew+]">
		<div class="installImg"><img src="img/install_new.png" alt="new install" /></div>
		<div class="installDetails">
			<h3><input type="radio" name="installmode" id="installmode1" value="0" [+checkedNew+] />
			<label for="installmode1" class="nofloat">[%installation_new_installation%]</label></h3>
			<p>[%installation_install_new_copy%] [+moduleName+]</p>
			<p><strong>[%installation_install_new_note%]</strong></p>
		</div>
	</div>
	<div style="overflow:hidden;[+displayUpg+]">
		<div class="installImg"><img src="img/install_upg.png" alt="upgrade existing install" /></div>
		<div class="installDetails">
			<h3><input type="radio" name="installmode" id="installmode2" value="1" [+disabledUpg+] [+checkedUpg+] />
			<label for="installmode2" class="nofloat">[%installation_upgrade_existing%]</label></h3>
			<p>[%installation_upgrade_existing_note%]</p>
		</div>
		<hr style="overflow:hidden;[+displayUpg+]" />
	</div>
	<div style="overflow:hidden;[+displayAdvUpg+]">
		<div class="installImg"><img src="img/install_adv.png" alt="advanced EVO upgrade" /></div>
		<div class="installDetails">
			<h3><input type="radio" name="installmode" id="installmode3" value="2" [+disabledAdvUpg+] [+checkedAdvUpg+] />
			<label for="installmode3" class="nofloat">[%installation_upgrade_advanced%]</label></h3>
			<p>[%installation_upgrade_advanced_note%]</p>
		</div>
	</div>

    <p class="buttonlinks">
        <a href="javascript:document.getElementById('install_form').action='index.php?action=language';document.getElementById('install_form').submit();" class="prev" title="[%btnback_value%]"><span>[%btnback_value%]</span></a>
        <a style="display:inline;" href="javascript:if(document.getElementById('installmode2').checked){document.getElementById('install_form').action='index.php?action=options';}document.getElementById('install_form').submit();" title="[%btnnext_value%]"><span>[%btnnext_value%]</span></a>
    </p>
</form>