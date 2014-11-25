<?php
$shortcodes = $_GET['shortcodes'];

var_dump($shortcodes);
?>

<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title>Shortcode Iframe</title>
    </head>
    <body>
        <script type="text/javascript">
        //<![CDATA[
        window.shortcodefinder = <?php $shortcodes; ?>
        //]]>
        </script>
    </body>
</html>

