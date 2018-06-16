<?php
if (!function_exists('bold')) {
    /**
     * @param bool $cond
     * @return string
     */
    function bold($cond = false)
    {
        return ($cond !== false) ? ' style="background-color:#777;color:#fff;"' : '';
    }
}


if (!function_exists('echoEventRows')) {
    function echoEventRows(&$evtnames)
    {
        echo '<div class="row form-row">
                <div class="col-sm-6 col-md-4 col-lg-3">' .
                    implode('</div><div class="col-sm-6 col-md-4 col-lg-3">', $evtnames) .
                '</div>' .
        '</div>';

        $evtnames = array();
    }
}
