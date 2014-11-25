<?php

/**
*
*/
class GwaShortcodePopup
{
    public function makeIframe()
    {
        global $gwaShortcode;

        $classId = $gwaShortcode->popupID;
        $codes   = json_encode($gwaShortcode->getShortcodes());

        return <<<EOF
<div id="$classId">
    <form id="gwa-post-form">
        <div id="gwa-select-wrapper" class="gwa-select-wrapper">
            <h2>Pick a shortcode:</h2>
            <ul></ul>
        </div><div id="gwa-attr-wrapper" class="gwa-attr-wrapper">
            <h2>Pick your attributes:</h2>
        </div>
        <button class="button button-primary button-large gwa-submit" id="gwa-submit" disabled>Insert into page</button>
    </form>

    <script>
        jQuery('body').addClass('gwa-modal');
        gwa.initShortcodeForm($codes);
    </script>
</div>
EOF;
    }
}
