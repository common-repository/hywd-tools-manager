<?php

namespace HywdPluginManager\Admin;

if (!defined('ABSPATH')) exit;

define( 'HWYD_CACHE_HR', 60 * 60 );


if (!function_exists('hywd_get_plugin_info')) {
    function hywd_get_plugin_info()
    {

        global $hywd_plugin_api_info; // Check if it's in the runtime cache (saves database calls)
        if (empty($hywd_plugin_api_info)) $hywd_plugin_api_info = get_transient('hywd_plugin_api_info'); // Check database (saves expensive HTTP requests)
        if (!empty($hywd_plugin_api_info)) {
            return $hywd_plugin_api_info;
        }

        $username = 'ck_95f69e5cbbc743fdbb93069fee931d12871fd035';
        $password = 'cs_83f2dfc6651c778bb99eba367d0d8c4e19ca18c9';

        $wp_request_headers = array(
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
        );

        $api_url = 'https://haywood.tools/wp-json/wc/v3/products?category=99';

        $products_response = wp_remote_get($api_url, array(
            'headers' => $wp_request_headers,
            'timeout' => 20 // Increase the timeout value (in seconds)
        ));

        if (!is_wp_error($products_response)) {
            $body = wp_remote_retrieve_body($products_response);
            $products = json_decode($body, 1);
            $products_arr = [];

            // check $products status
            $response_code = wp_remote_retrieve_response_code($products_response);
            // Check if the response code is 404
            if ($response_code >= 299) {
                echo "<div style='
                            margin-top: 30vh;
                            font-size: 20px;
                            font-weight: bold;
                            background: white;
                            padding: 30px;
                            border-radius: 10px;
                            width: 90%;'>
                            " . esc_html('Something went wrong. Please contact plugin support.') . "
                        </div>";
                wp_die();
            }

            if (is_array($products)) {
                $products_arr = array_map(function ($product) {
                    $unique_title = '';
                    $icon = '';
                    $license_enabled = '';
                    $plugin_name = '';
                    if (isset($product['meta_data'])) {
                        foreach ($product['meta_data'] as $meta) {
                            if ($meta['key'] === '_sl_software_title') {
                                $plugin_name = $meta['value'];
                            }
                            if ($meta['key'] === '_sl_software_unique_title') {
                                $unique_title = $meta['value'];
                            }
                            if ($meta['key'] === '_sl_enabled') {
                                $license_enabled = $meta['value'];
                            }
                            if ($meta['key'] === '_sl_icons_svg') {
                                $icon = $meta['value'];
                            }
                        }
                    }

                    if ($plugin_name == '' && isset($product['name'])) {
                        $plugin_name = $product['name'];
                    }

                    $icon_type = 'png/jpg';

                    $extension = pathinfo($icon, PATHINFO_EXTENSION);
                    // Check if the icon is an SVG or a JPG/PNG
                    if (strtolower($extension) === 'svg') {
                        $icon_url_respo = wp_remote_get($icon, ['timeout' => 20]);
                        $icon_type = 'svg';
                        // Check if the request was successful
                        if (!is_wp_error($icon_url_respo)) {
                            // Retrieve the response code
                            $response_code = wp_remote_retrieve_response_code($icon_url_respo);

                            // Check if the response code is 404
                            if ($response_code >= 200 && $response_code <= 299) {
                                // Retrieve the body of the response if not a 404
                                $body = wp_remote_retrieve_body($icon_url_respo);

                                // Echo the body
                                $icon = $body;
                            } else {
                                $icon = '';
                            }
                        }

                    }

                    if ($license_enabled === 'yes') {
                        return array(
                            'name' => $plugin_name,
                            'description' => wp_strip_all_tags($product['description']),
                            'icon' => $icon,
                            'icon_type' => $icon_type,
                            'unique_id' => $unique_title,
                            'license_name' => 'license-' . $unique_title,
                            'installed_status' => 0,
                            'active_status' => 0,
                            'license_status' => 0,
                            'main_file' => '',
                            'shop' => $product['permalink']
                        );
                    } else {
                        return null;
                    }
                }, $products);

                // Remove null values from the array
                $products_arr = array_filter($products_arr);

            }
        } else {
            echo "<div class='hywd-error-container'>";
            $error_message = $products_response->get_error_message();
            // Check if the error message contains a timeout indication
            if (strpos($error_message, 'Operation timed out') !== false) {
                echo '<h3>' . esc_html("Error: The request to the API timed out. Please refreshing the page or contacting the plugin developer.") . '</h3>';
            } else if (strpos($error_message, 'Could not resolve host') !== false) {
                echo '<h3>' . esc_html("Error: No Internet Connection") . '</h3>';
            } else {
                echo '<h3>' . esc_html('Error: ' . $error_message) . '</h3>';
            }
            echo "</div>";
            exit;
        }


        set_transient('hywd_plugin_api_info', $products_arr, 12 * HWYD_CACHE_HR); // Store in database for up to 12 HWYD_CACHE_HR

        return $products_arr;
    }
}

if (!function_exists('hywd_plugin_status_check')) {
    function hywd_plugin_status_check($plugin)
    {
        $plugin_unique_id = $plugin['unique_id'];

        $lk_details = get_option('license-' . $plugin_unique_id);

        if ($lk_details == '' || count($lk_details) == 0 || trim($lk_details['key']) == '') {
            return 'No License';
        }

        $lk_key = $lk_details['key'];

        $args = array(
            'woo_sl_action' => 'status-check',
            'licence_key' => $lk_key,
            'product_unique_id' => $plugin_unique_id,
            'domain' => HYWD_PLUGIN_MANAGER_SLT_INSTANCE
        );
        $request_uri = HYWD_PLUGIN_MANAGER_SLT_APP_API_URL . '?' . http_build_query($args, '', '&');
        $response = wp_remote_get($request_uri);

        if (!is_wp_error($response)) {
            if ($response['response']['code'] == 500) {
                return "<span style='color:red;'>SERVER ERROR</span>";
            }

            $body = wp_remote_retrieve_body($response);
            $api_response = json_decode($body, true);

            // Log the API response for debugging
            error_log('API Response: ' . print_r($api_response, true));

            //retrieve the last message within the $response_block
            $response_block = $api_response[count($api_response) - 1];
            $response_message = $response_block['message'];

            // Process the API response
            if ($response_block['status'] == 'success' && ($response_block['status_code'] == 's205' || $response_block['status_code'] == 's215')) {
//            return $response_message;

                if (array_key_exists("licence_expire", $response_block)) {
                    $date1 = date_create(date("Y-m-d"));
                    $date2 = date_create($response_block['licence_expire']);
                    $diff = date_diff($date1, $date2);

                    $remaining = $diff->format("%R%a");
                }

                if (isset($remaining) && $remaining <= 10) {
                    if ($remaining > 0) {
                        return 'License expires in ' . $remaining . ngettext(" day", " days", $remaining);
                    } else {
                        return 'License Expired';
                    }
                } else if ($response_block['licence_status'] == 'active') {
                    return 'License Active';
                }
            } else {
                $message = 'License validation failed: ' . $response_message;
                $message .= '<br> Please refresh the page or contact plugin developers';
                return $message;
            }
        }

        // Return the response
        wp_die(); // Always include this at the end of an Ajax callback

    }
}

if (!function_exists('hywd_verify_lk')) {
    function hywd_verify_lk()
    {
        // Verify the nonce

        if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'ajax-nonce')) {
            echo wp_json_encode(array('status' => 'error', 'message' => 'Security check failed. Please try again.'));
            wp_die();
        }

        $lk_key = isset($_POST['license_key']) ? sanitize_text_field($_POST['license_key']) : '';
        $plugin_unique_id = isset($_POST['plugin_unique_id']) ? sanitize_text_field($_POST['plugin_unique_id']) : '';
        $api_action = isset($_POST['api_action']) ? sanitize_text_field($_POST['api_action']) : '';


        $args = array(
            'woo_sl_action' => $api_action,
            'licence_key' => $lk_key,
            'product_unique_id' => $plugin_unique_id,
            'domain' => HYWD_PLUGIN_MANAGER_SLT_INSTANCE
        );
        $request_uri = HYWD_PLUGIN_MANAGER_SLT_APP_API_URL . '?' . http_build_query($args, '', '&');
        $response = wp_remote_get($request_uri);


        if (!is_wp_error($response)) {
            $body = wp_remote_retrieve_body($response);
            $api_response = json_decode($body, true);

            if ($response['response']['code'] == 500) {
                echo wp_json_encode(array('status' => 'error', 'message' => 'Server Error. Please try again later.'));
            }

            // Log the API response for debugging
//        error_log('API Response: ' . print_r($api_response, true));

            //retrieve the last message within the $response_block
            $response_block = $api_response[count($api_response) - 1];
            $response_message = $response_block['message'];

            // Process the API response
            if ($response_block['status'] == 'success' && ($response_block['status_code'] == 's100' || $response_block['status_code'] == 's101' || $response_block['status_code'] == 's201')) {

                //save the lk
                if ($api_action == 'activate') {
                    $lk_data['key'] = $lk_key;
                    $lk_data['last_check'] = time();

                    echo wp_json_encode(array('status' => 'success', 'message' => $response_message));
                } else {
                    $lk_data = '';
                    echo wp_json_encode(array('status' => 'deactivated', 'message' => $response_message));
                }

                update_option('license-' . $plugin_unique_id, $lk_data);
            } else {
                $message = 'License validation failed: ' . $response_message;
                echo wp_json_encode(array('status' => 'error', 'message' => $message));
            }
        } else {
            $message = 'Error validating license: ' . $response->get_error_message();
            echo wp_json_encode(array('status' => 'error', 'message' => $message));
        }

        // Return the response
        wp_die(); // Always include this at the end of an Ajax callback

    }
    add_action('wp_ajax_hywd_verify_lk', 'HywdPluginManager\Admin\hywd_verify_lk');
    add_action('wp_ajax_nopriv_hywd_verify_lk', 'HywdPluginManager\Admin\hywd_verify_lk'); // For non-logged-in users
}
