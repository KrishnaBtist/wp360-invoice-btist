<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
require_once('custom_plugin_update_count.php');

add_action('wp_head', function(){
    $aviliable_version = get_option('wp360_plugin_available_version');
    echo '<pre> Aviliable Version',var_dump( $aviliable_version ); echo '</pre>';
    echo '<pre> Current Version',var_dump(  get_plugin_version()  ); echo '</pre>';
    //remove_custom_transient();
});











