

<?php
if (extension_loaded('gd') && function_exists('gd_info')) {
    echo 'GD is enabled: <pre>';
    print_r(gd_info());
    echo '</pre>';
} else {
    echo 'GD is not enabled';
}
?>