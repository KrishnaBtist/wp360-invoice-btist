<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
// Define a secret key to authenticate requests from Git webhook
$secret_key = 'wp360';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payload']) && !empty($_POST['payload'])) {
   // error_log("custom ERROR PAYLOAD" .  json_encode($_POST['payload']));
    $headers    = getallheaders();
    $signature  = $headers['X-Hub-Signature'] ?? '';
    if ($signature !== 'sha1=' . hash_hmac('sha1', file_get_contents('php://input'), $secret_key)) {
        header('HTTP/1.0 403 Forbidden');
        die('Forbidden');
    }
    $payload = json_decode($_POST['payload'], true);
    $repo_name = $payload['repository']['full_name'] ?? '';
    $pusher_name = $payload['pusher']['name'] ?? '';
    $release_version = $payload['release']['tag_name'] ?? '';
    $release_Title = $payload['release']['body'] ?? '';
    $release_Description = $payload['release']['name'] ?? '';

    error_log('Release Array: ' . json_encode($payload['release']));
    error_log('Release Version: ' . $release_version);
    error_log('Current Version: ' . get_plugin_version());
    // Perform version comparison if $release_version is not null
    if (!empty($release_version) && version_compare(get_plugin_version(), $release_version, '<')) {
        error_log('Greater than current version');
        update_option('wp360_plugin_available_version', $release_version);
    }
}
// add_action('wp_head', function(){
//     $aviliable_version = get_option('wp360_plugin_available_version');
//     echo '<pre> Aviliable Version',var_dump( $aviliable_version ); echo '</pre>';
//     echo '<pre> Current Version',var_dump(  get_plugin_version()  ); echo '</pre>';
// });
// add_action('admin_notices', 'custom_plugin_display_status');
// function custom_plugin_display_status() {
//     $aviliable_version     = get_option('wp360_plugin_available_version');
//     $current_version       = get_plugin_version();
//     if ($current_version != $aviliable_version) {
//         //$status = 'Plugin update available Version ' .$aviliable_version;
//         printf(
//             '<div class="notice notice-error wp360_invoice_current_version">
//                 <p>%s</p>
//             </div>',
//             esc_attr(sprintf(__('wp360 invoice update available Version %s', 'wp360-invoice'), $aviliable_version))
//         );
//     }
//   //  echo '<div class="notice notice-info">' . $status . '</div>';
// }
add_action('after_plugin_row', 'custom_plugin_update_notice', 10, 2);
function custom_plugin_update_notice($plugin_file, $plugin_data) {
    if ("wp360-invoice/wp360-invoice.php" === $plugin_file) {
        $aviliable_version    =  get_option('wp360_plugin_available_version');
        if (get_plugin_version() !=  $aviliable_version) {
            ?>
            <tr class="plugin-update-tr">
                <td colspan="3" class="plugin-update colspanchange">
                    <div class="update-message notice inline notice-warning notice-alt">
                        <p><?php 
                            printf(
                                __('There is a new version of %s available 22. <a href="">View version %s details</a> or <a href="#" class="%s">Update now.</a>', 'wp360-invoice'),
                                'WP360 Invoice', // Plugin name
                                esc_html($aviliable_version), // Available version
                                'wp360-invoice-update-click' // Update now link class
                            );
                        ?></p>
                    </div>
                </td>
            </tr>
            <script>
                jQuery(document).ready(function($) {
                    $('.wp360-invoice-update-click').on('click', function(e) {
                        e.preventDefault();
                        $this =  jQuery(this);
                        var data = {
                            'action': 'update_wp360_invoice' // Action to handle in PHP
                        };
                        $.ajax({
                            url: ajaxurl, // WordPress AJAX URL
                            type: 'POST',
                            data: data,
                            beforeSend: function() {
                                $('.update-message').append('<span class="updating-message">Updating...</span>');
                            },
                            success: function(response) {
                                console.log(response);
                                let responseData = JSON.parse(response);
                                console.log(JSON.stringify(responseData.aviliableVersion));

                                var trElement = $('tr[data-slug="wp360-invoice"]');
                                var divElement = trElement.find('.plugin-version-author-uri');
                                divElement.html('Version ' + responseData.aviliableVersion + ' | By <a href="https://wp360.in/">wp360</a>');

                              //  $this.closest('.plugin-version-author-uri').text(');
                                $('.updating-message').remove();
                                $('.plugin-update-tr').remove();
                            },
                            error: function(xhr, status, error) {
                                console.error(error);
                                $('.updating-message').remove();
                            }
                        });
                    });
                });
            </script>
            <?php
        }
    }
}
// Clone Repository from GitHub/
add_action('wp_ajax_update_wp360_invoice', 'update_wp360_invoice_callback');
function update_wp360_invoice_callback() {
    if(isset($_POST['action']) &&  $_POST['action'] == "update_wp360_invoice"){
        $aviliable_version = get_option('wp360_plugin_available_version');
        $plugin_dir     = plugin_dir_path(__FILE__);
        require_once $plugin_dir . 'vendor/autoload.php';
        $repoOwner      = 'KrishnaBtist';
        $repoName       = 'wp360-invoice-btist';
        $branch         = 'main'; 
        $token          = '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'; // Replace this with your actual personal access token
        $apiUrl         = "https://api.github.com/repos/{$repoOwner}/{$repoName}/contents";
        $clonePath      = plugin_dir_path(__FILE__);
        // Initialize GuzzleHttp client
        $client = new GuzzleHttp\Client();
        fetchFilesFromDirectory($client, $apiUrl, $clonePath, $token);
        
       // if($successupdate){
           echo json_encode(
                array(
                    'success' => true,
                    'aviliableVersion'=>$aviliable_version
                ),
            );
       // }
      
    }
    wp_die();
}
function fetchFilesFromDirectory($client, $apiUrl, $localDirectory, $token) {
    $headers = [
        'Authorization' => 'token ' . $token,
        'Accept' => 'application/vnd.github.v3+json',
    ];
    // Send request to GitHub API to get repository contents
    $response = $client->request('GET', $apiUrl, [
        'headers' => $headers,
    ]);
    $files = json_decode($response->getBody(), true);
    // Iterate through each file in the repository
    foreach ($files as $file) {
        if ($file['type'] === 'file') {
            $fileContent = file_get_contents($file['download_url']);
            $localFilePath = $localDirectory . '/' . $file['name'];
            if (file_exists($localFilePath)) {
                file_put_contents($localFilePath, $fileContent);
                error_log(' File '.$file['name'].' updated locally 1. <br>');
            } else {
                file_put_contents($localFilePath, $fileContent);
                error_log('File '.$file['name'].' saved locally 1 <br>');
            }
        } elseif ($file['type'] === 'dir') {
            $subDirectoryUrl = $file['url'];
            $subDirectoryName = $file['name'];
            $subLocalDirectory = $localDirectory . '/' . $subDirectoryName;
            if (!file_exists($subLocalDirectory)) {
                mkdir($subLocalDirectory, 0777, true);
            }
            fetchFilesFromDirectory($client, $subDirectoryUrl, $subLocalDirectory, $token);
        }
    }
}



// Add action hook to wp_head
// add_action('wp_head', function() {
//     $plugin_dir = plugin_dir_path(__FILE__);
//     require_once $plugin_dir . 'vendor/autoload.php';
//     $repoOwner = 'KrishnaBtist';
//     $repoName = 'wp360-invoice-btist';
//     $branch = 'main'; 
//     $token = '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'; // Replace this with your actual personal access token
//     // GitHub API endpoint to get the repository contents
//     $apiUrl = "https://api.github.com/repos/{$repoOwner}/{$repoName}/contents";
//     // Path where you want to clone the repository
//     $clonePath = plugin_dir_path(__FILE__);
//     // Initialize GuzzleHttp client
//     $client = new GuzzleHttp\Client();
//     // Function to fetch files from a directory
//     function fetchFilesFromDirectory($client, $apiUrl, $localDirectory, $token) {
//         $headers = [
//             'Authorization' => 'token ' . $token,
//             'Accept' => 'application/vnd.github.v3+json',
//         ];
//         // Send request to GitHub API to get repository contents
//         $response = $client->request('GET', $apiUrl, [
//             'headers' => $headers,
//         ]);
//         $files = json_decode($response->getBody(), true);
//         // Iterate through each file in the repository
//         foreach ($files as $file) {
//             if ($file['type'] === 'file') {
//                 $fileContent = file_get_contents($file['download_url']);
//                 $localFilePath = $localDirectory . '/' . $file['name'];
//                 if (file_exists($localFilePath)) {
//                     file_put_contents($localFilePath, $fileContent);
//                     error_log(' File '{$file['name']}' updated locally 1.');
//                 } else {
//                     file_put_contents($localFilePath, $fileContent);
//                     error_log('File '{$file['name']}' saved locally 1 <br>')
//                 }
//             } elseif ($file['type'] === 'dir') {
//                 $subDirectoryUrl = $file['url'];
//                 $subDirectoryName = $file['name'];
//                 $subLocalDirectory = $localDirectory . '/' . $subDirectoryName;
//                 if (!file_exists($subLocalDirectory)) {
//                     mkdir($subLocalDirectory, 0777, true);
//                 }
//                 fetchFilesFromDirectory($client, $subDirectoryUrl, $subLocalDirectory, $token);
//             }
//         }
//     }
//     fetchFilesFromDirectory($client, $apiUrl, $clonePath, $token);
// });


