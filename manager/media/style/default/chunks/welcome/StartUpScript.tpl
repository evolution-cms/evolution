<script type="text/javascript">
    function hideConfigCheckWarning(key) {
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "index.php?a=118", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.onload = function() {
            if(this.readyState === 4) {
                var fieldset = document.getElementById(key + "_warning_wrapper").parentNode.parentNode;
                fieldset.className = "collapse";
            }
        };
        var tokenMeta = document.querySelector('meta[name="csrf-token"]');
        xhr.send("_token=" + encodeURIComponent(tokenMeta ? tokenMeta.content : "") + "&action=setsetting&key=_hide_configcheck_" + key + "&value=1");
    }
    (function($) {
        $("[data-toggle='collapse']").click(function(e) {
            // Let links inside a toggle row navigate; a toggle that is itself a link (the info button) still toggles.
            var link = $(e.target).closest("a");
            if(link.length && !link.is(this)) return;
            if($(this).data("target")) {
                $($(this).data("target")).toggleClass("in")
            }
        });
    })(jQuery);
</script>
