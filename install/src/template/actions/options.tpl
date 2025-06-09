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
        <input type="hidden" value="[+database_type+]" name="database_type" />
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
    <h4>[%checkbox_select_options%]</h4>
    <p class="actions">
        <a class="toggle_check_all" href="#">[%all%]</a>
        <a class="toggle_check_none" href="#">[%none%]</a>
        <a class="toggle_check_toggle" href="#">[%toggle%]</a>
    </p>
    <br class="clear" />
    <div id="installChoices" class="my-3">
        <div class="templates">[+templates+]</div>
        <div class="tvs">[+tvs+]</div>
        <div class="chunks">[+chunks+]</div>
        <div class="modules">[+modules+]</div>
        <div class="plugins">[+plugins+]</div>
        <div class="snippets">[+snippets+]</div>
    </div>
    <p class="buttonlinks">
        <button type="button" class="prev" title="[%btnback_value%]"><span>[%btnback_value%]</span></button>
        <button type="button" class="next" title="[%btnnext_value%]"><span>[%btnnext_value%]</span></button>
    </p>

</form>
<script type="text/javascript" nonce="[+csrf_nonce+]">
  document.querySelector('.buttonlinks .prev').onclick = () => {
    document.getElementById('install_form').action = 'index.php?action=[+action+]';
    document.getElementById('install_form').submit();
  }
  document.querySelector('.buttonlinks .next').onclick = () => {
    document.getElementById('install_form').submit();
  }

  document.querySelectorAll('#installChoices h3').forEach(function (h3) {
    const span = document.createElement('span');
    h3.append(span);
    span.classList.add('actions');
    span.innerHTML = document.querySelector('p.actions').innerHTML;
  });
  document.querySelector('#installChoices').parentNode.addEventListener('click', function (e) {
    const a = e.target.closest('a');
    if (!a || !a.classList.contains('toggle_check_all') &&
      !a.classList.contains('toggle_check_none') &&
      !a.classList.contains('toggle_check_toggle')) {
      return;
    }

    e.preventDefault();

    let checkboxes = Array.from(document.querySelectorAll('input[type=checkbox].toggle:not(:disabled)'));

    if (this.contains(a.parentElement)) {
      const container = a.parentElement.closest('div');
      if (container) {
        checkboxes = Array.from(container.querySelectorAll('input[type=checkbox].toggle:not(:disabled)'));
      }
    }

    if (a.classList.contains('toggle_check_all')) {
      checkboxes.forEach(cb => cb.checked = true);
    } else if (a.classList.contains('toggle_check_none')) {
      checkboxes.forEach(cb => cb.checked = false);
    } else if (a.classList.contains('toggle_check_toggle')) {
      checkboxes.forEach(cb => cb.checked = !cb.checked);
    }
  });
</script>