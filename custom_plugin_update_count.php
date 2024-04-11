<?php

add_filter('pre_set_site_transient_update_plugins', 'custom_plugin_update_count');
function custom_plugin_update_count($transient) {
    $custom_plugin_file = 'wp360-invoice/wp360-invoice.php';
    $available_version = get_option('wp360_plugin_available_version');
    $installed_version = get_plugin_data(WP_PLUGIN_DIR . '/' . $custom_plugin_file)['Version'];
    if (!empty($available_version) && version_compare($available_version, $installed_version, '>')) {
        // Increment the update count
        if (!isset($transient->response[$custom_plugin_file])) {
            $transient->response[$custom_plugin_file] = new stdClass();
        }
        // Set update details
        $transient->response[$custom_plugin_file]->new_version = $available_version;
    }
    return $transient;
}




// function delete_custom_plugin_transient($upgrader_object, $options) {
//     if ($options['type'] === 'plugin' && isset($options['plugins']) && is_array($options['plugins'])) {
//         $custom_plugin_file = 'wp360-invoice/wp360-invoice.php';
//         if (in_array($custom_plugin_file, $options['plugins'])) {
//             delete_transient('custom_plugin_update_count');
//         }
//     }
// }


add_action('admin_init', 'clear_plugin_updates');
function clear_plugin_updates() {
    delete_site_transient('update_plugins');
}



