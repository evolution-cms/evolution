<div class="stepcontainer">
      <ul class="progressbar">
          <li class="active">[%choose_language%]</li>
          <li>[%installation_mode%]</li>
          <li>[%optional_items%]</li>
          <li>[%preinstall_validation%]</li>
          <li>[%install_results%]</li>
  </ul>
  <div class="clearleft"></div>
</div>
<form name="install" id="install_form" action="index.php?action=mode" method="post">
    <h2>[%choose_language%]:&nbsp;&nbsp;</h2>
    <select name="language">
        [+langOptions+]
    </select>
        <p class="buttonlinks">
            <button id="next" title="[%begin%]" type="submit">
            <span>[%btnnext_value%]</span></button>
        </p>
</form>
