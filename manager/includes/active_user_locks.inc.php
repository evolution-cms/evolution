<?php
if( ! defined('IN_MANAGER_MODE') || IN_MANAGER_MODE !== true) {
    die("<b>INCLUDE_ORDERING_ERROR</b><br /><br />Please use the EVO Content Manager instead of accessing this file directly.");
}

if (is_numeric($lockElementId) && ($lockElementId > 0 || $lockElementId == 0)) {
    ?>
    <script>
      // Trigger unlock when leaving window
      var form_save = false;

      window.addEventListener('pagehide', unlockThisElement, false);

      function unlockThisElement(event)
      {
        if (event && event.persisted) {
          return;
        }
        var stay = document.getElementById('stay');
        // Trigger unlock
        if ((stay && stay.value !== '2') || !form_save) {
          var url = '<?php echo EVO_MANAGER_URL; ?>?a=67&type=<?php echo $lockElementType;?>&id=<?php echo $lockElementId;?>&o=' + Math.random();
          if (navigator.sendBeacon) {
            navigator.sendBeacon(url)
          } else {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', url, false);
            xhr.send()
          }
          if (top.mainMenu) top.mainMenu.reloadtree()
        }
      }
    </script>
    <?php
}
?>
