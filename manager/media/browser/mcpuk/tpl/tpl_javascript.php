<script src="js/customEvent.js" type="text/javascript"></script>
<script src="js/jquery.js" type="text/javascript"></script>
<script src="js/jquery.rightClick.js" type="text/javascript"></script>
<script src="js/jquery.drag.js" type="text/javascript"></script>
<script src="js/helper.js" type="text/javascript"></script>
<script src="js/FileAPI/FileAPI.min.js" type="text/javascript"></script>
<script src="js/FileAPI/FileAPI.exif.js" type="text/javascript"></script>
<script src="js/browser/joiner.php" type="text/javascript"></script>
<script src="js_localize.php?lng=<?php echo $this->lang ?>" type="text/javascript"></script>
<?php IF (isset($this->opener['TinyMCE']) && $this->opener['TinyMCE']): ?>
<script src="<?php echo $this->config['_tinyMCEPath'] ?>/tiny_mce_popup.js" type="text/javascript"></script>
<?php ENDIF ?>
<?php IF (file_exists("themes/{$this->config['theme']}/init.js")): ?>
<script src="themes/<?php echo $this->config['theme'] ?>/init.js" type="text/javascript"></script>
<?php ENDIF ?>
<script type="text/javascript">
browser.version = "<?php echo self::VERSION ?>";
browser.support.chromeFrame = <?php echo (strpos(strtolower($_SERVER['HTTP_USER_AGENT']), " chromeframe") !== false) ? "true" : "false" ?>;
browser.support.zip = <?php echo (class_exists('ZipArchive') && !$this->config['denyZipDownload']) ? "true" : "false" ?>;
browser.lang = "<?php echo text::jsValue($this->lang) ?>";
browser.type = "<?php echo text::jsValue($this->type) ?>";
browser.theme = "<?php echo text::jsValue($this->config['theme']) ?>";
browser.access = <?php echo json_encode($this->config['access']) ?>;
browser.dir = "<?php echo text::jsValue($this->session['dir']) ?>";
browser.siteURL = "<?php echo text::jsValue($this->config['siteURL']) ?>";
browser.assetsURL = "<?php echo text::jsValue($this->config['assetsURL']) ?>";
browser.thumbsURL = browser.assetsURL + "/<?php echo text::jsValue($this->config['thumbsDir']) ?>";
browser.clientResize = <?php echo json_encode($this->config['clientResize']) ?>;
browser.allowedExts = /<?php echo str_replace(' ', '|', strtolower(text::clearWhitespaces($this->types[$this->type]))) ?>$/;
browser.deniedExts = /<?php echo str_replace(' ', '|', strtolower(text::clearWhitespaces($this->config['deniedExts']))) ?>$/;
browser.maxFileSize = <?php echo text::jsValue($this->config['maxfilesize']) ?>;
<?php IF (isset($this->get['opener']) && strlen($this->get['opener'])): ?>
browser.opener.name = "<?php echo text::jsValue($this->get['opener']) ?>";
<?php ENDIF ?>
<?php IF (isset($this->opener['CKEditor']['funcNum']) && preg_match('/^\d+$/', $this->opener['CKEditor']['funcNum'])): ?>
browser.opener.CKEditor = {};
browser.opener.CKEditor.funcNum = <?php echo $this->opener['CKEditor']['funcNum'] ?>;
<?php ENDIF ?>
<?php IF (isset($this->opener['TinyMCE']) && $this->opener['TinyMCE']): ?>
browser.opener.TinyMCE = true;
<?php ENDIF ?>
<?php IF (isset($this->get['opener']) && ($this->get['opener'] == "tinymce4") && isset($this->get['field'])): ?>
browser.opener.TinyMCE4 = "<?= text::jsValue($this->get['field']) ?>";
<?php ENDIF ?>
browser.cms = "<?php echo text::jsValue($this->cms) ?>";
_.kuki.domain = "<?php echo text::jsValue($this->config['cookieDomain']) ?>";
_.kuki.path = "<?php echo text::jsValue($this->config['cookiePath']) ?>";
_.kuki.prefix = "<?php echo text::jsValue($this->config['cookiePrefix']) ?>";
$(document).ready(function() {
    (function(w, d){
        var b = d.getElementsByTagName('body')[0];
        var s = d.createElement("script"); s.async = true;
        var v = !("IntersectionObserver" in w) ? "8.7.1" : "10.5.2";
        s.src = "js/lazyload/" + v + "/lazyload.min.js";
        w.lazyLoadOptions = {
            container: d.getElementById('files'),
            elements_selector: ".lazy"
        }; // Your options here. See "recipes" for more information about async.
        b.appendChild(s);
        w.addEventListener('LazyLoad::Initialized', function (e) {
            // Get the instance and puts it in the lazyLoadInstance variable
            lazyLoadInstance = e.detail.instance;
            browser.resize();
            browser.init();
            browser.fitParentIFrame();
            $('#all').css('visibility', 'visible');
        }, false);
    }(window, document));
});
$(window).resize(function() {
    browser.resize();
    browser.fitParentIFrame();
});
</script>
