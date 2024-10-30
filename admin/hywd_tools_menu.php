<?php

namespace HywdPluginManager\Admin;

if (!defined('ABSPATH')) exit;
require_once ABSPATH . 'wp-admin/includes/plugin.php';

if (!function_exists('hywd_get_kses_extended_ruleset')) {
    function hywd_get_kses_extended_ruleset()
    {
        $kses_defaults = wp_kses_allowed_html('post');

        $svg_args = array(
            'svg' => array(
                'class' => true,
                'aria-hidden' => true,
                'aria-labelledby' => true,
                'role' => true,
                'xmlns' => true,
                'width' => true,
                'height' => true,
                'viewbox' => true, // <= Must be lower case!
            ),
            'g' => array('fill' => true),
            'title' => array('title' => true),
            'path' => array(
                'd' => true,
                'fill' => true,
            ),
        );
        return array_merge($kses_defaults, $svg_args);
    }
}

if (!function_exists('hywd_plugin_manager_options')) {
    function hywd_plugin_manager_options() {

        $products_arr = hywd_get_plugin_info();

        // get active plugin list
        $wp_plugins = get_plugins();

        foreach ($products_arr as &$hywd_plugin){
            foreach ($wp_plugins as $file => $plugin){
                if(trim($plugin['Name']) == trim($hywd_plugin['name'])){
                    $hywd_plugin['installed_status'] = 1;
                    $hywd_plugin['main_file'] = $file;
                    if ( is_plugin_active( $file ) ) {
                        $hywd_plugin['active_status'] = 1;
                    }
                }
            }
        }

        ?>
        <div class="hywd-plugin-manager-container">
            <div class="hywd-pm-header">
                <div class="pm-left-header-content">
                    <h1>HAYWOOD Digital Tools</h1>
                    <p>Your best tools. Just better.</p>
                </div>

                <div class="pm-right-header-content">
                    <a href="https://haywood.tools/shop/" target="_blank" class="hywd-pm-btn-green">Visit Shop</a>
                    <a href="https://haywood.tools/my-account/" target="_blank" class="hywd-pm-btn-green">Manage Your Downloads & Licenses</a>
                </div>
            </div>
            <div class="hywd-pm-table-container">
                <table>
                    <thead>
                        <tr class="hywd-pm-table-header">
                            <th>Plugin</th>
                            <th style="text-align: center;">Info</th>
                            <th>Status</th>
                            <th>Enter License</th>
                            <th>Activate</th>
                            <th>Shop</th>
                        </tr>
                    </thead>


                <?php

                foreach ($products_arr as $plugin){

                    ?>
                    <tr class="hywd-pm-table-plugin-info <?php echo $plugin['installed_status'] == 1 ? 'plugin-installed' : 'plugin-not-installed'; ?>">
                        <td class="hywd-plugin-name-tab">
                            <div class="hywd-name-icon">
                                <?php
                                $icon_url = $plugin['icon'];
                                $icon_type = $plugin['icon_type'];

                                if ( $icon_type === 'svg' ) {
                                    echo "<span class='hywd-plugin-logo'>";
    //	                            echo $icon_url;
                                    echo wp_kses($icon_url, hywd_get_kses_extended_ruleset());
                                    echo "</span>";
                                } else {
                                    echo '<img src="'.esc_url($icon_url).'" alt="'.esc_attr($plugin['name']).'" class="hywd-plugin-logo">';
                                }

                                ?>
                                <p class="hywd-plugin-name"><?php echo esc_html($plugin['name']); ?></p>
                            </div>

                        </td>

                        <td class="hywd-plugin-info">
                            <div class="hywd-plugin-info-popup" data-title="<?php echo esc_attr($plugin['description']); ?>">
                                <img src="<?php echo esc_url(HYWD_PLUGIN_MANAGER_URL).'assets/icons/help.svg';?>" alt="<?php echo esc_attr($plugin['name']); ?>">
                            </div>
                        </td>

                        <td class="hywd-plugin-status">
                            <?php
                                if($plugin['installed_status'] == 1){
                                    echo '<p class="hywd-plugin-install-status">'.esc_html('Installed').'</p>';
                                }else{
                                    echo '<p class="hywd-plugin-install-status">'.esc_html('Not installed').'</p>';
                                }

                                $plugin_lk_status = hywd_plugin_status_check($plugin);
                            ?>
                            <p class="hywd-plugin-license-status"><?php echo esc_html($plugin_lk_status); ?></p>
                        </td>

                        <td class="hywd-plugin-license-form">
                            <form class="hywd-license-verification-form">
                                <?php
                                $lk_details = get_option('license-'.$plugin['unique_id']);
                                if($plugin_lk_status == 'No License'){
                                    echo '<input type="text" id="hywd-license-key-input" width="100%" placeholder="Enter your license here...">';
                                    echo '<button type="submit" class="license-verify-btn right-arrow"><svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M26.5443 17.2783L15.6448 7.05142C15.1669 6.58167 14.262 6.52295 13.6418 6.9242C13.0216 7.32544 12.9097 8.03007 13.2452 8.49003L23.1482 17.8573L10.2152 26.489C9.59497 26.8902 9.73747 27.7988 10.2153 28.2587C10.6932 28.7285 11.5981 28.7872 12.2183 28.3859L26.4731 18.9322C27.073 18.5114 27.0527 17.6991 26.5545 17.2685L26.5443 17.2783Z" fill="#ABCE4A"/>
                                            </svg>
                                            </button>';
                                    echo '<button type="button" class="license-verify-btn license-edit-btn invisible"></button>';
                                }else{
                                    $str = $lk_details['key'];
                                    $parts = explode('-', $str);
                                    $lastPart = end($parts);
                                    $encoded = base64_encode($str);
                                    $info_popup_text = 'Click to add a new license for this plugin. To only deactivate the license you entered, to free it for other domains, you can just delete the current entry and send the empty field.';

                                    $partial_lk = str_repeat('*', strlen($str) - strlen($lastPart)) . $lastPart;
                                    echo '<input type="text" data-license="'.esc_attr($encoded).'" disabled id="hywd-license-key-input" class="hywd-license-input-disabled" width="100%" value="'.esc_attr($partial_lk).'"  placeholder="Enter your license here..." >';
                                    echo '<button type="button" class="license-verify-btn normal-cursor check-icon">
                                            <svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M12.5125 27.1436C12.2996 27.1207 12.0866 27.0635 11.9004 26.9605C11.7141 26.8574 11.5544 26.7201 11.4302 26.5598L4.2445 17.1858C4.1203 17.0255 4.04046 16.8653 3.98723 16.6821C3.934 16.5105 3.92513 16.3273 3.94287 16.1327C3.96062 15.9382 4.02272 15.755 4.11143 15.5833C4.20901 15.4002 4.33321 15.2514 4.48402 15.1141C4.6437 14.9767 4.82112 14.8737 5.01629 14.8165C5.1671 14.7707 5.31791 14.7364 5.47759 14.7364H5.60179C5.80583 14.7592 6.001 14.8165 6.16955 14.9195C6.3381 15.0111 6.48004 15.137 6.59537 15.2972L12.9738 23.6183L30.5476 8.53274C30.7073 8.39539 30.8848 8.30382 31.071 8.23515C31.2219 8.18937 31.3815 8.15503 31.5324 8.15503H31.6565C31.8517 8.17792 32.038 8.23515 32.2154 8.32672C32.384 8.41828 32.5259 8.54419 32.6413 8.69298C32.863 8.97913 32.9606 9.35684 32.9163 9.7231C32.8719 10.1008 32.6767 10.4671 32.3751 10.7303L13.7278 26.743C13.5593 26.8918 13.3641 27.0062 13.1512 27.0749C12.9826 27.1321 12.8052 27.155 12.6367 27.155C12.5923 27.155 12.548 27.155 12.5036 27.155L12.5125 27.1436Z" fill="#ABCE4A"/>
                                            </svg>
                                        </button>';
                                    echo '<button type="submit" class="license-verify-btn right-arrow hidden"><svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M26.5443 17.2783L15.6448 7.05142C15.1669 6.58167 14.262 6.52295 13.6418 6.9242C13.0216 7.32544 12.9097 8.03007 13.2452 8.49003L23.1482 17.8573L10.2152 26.489C9.59497 26.8902 9.73747 27.7988 10.2153 28.2587C10.6932 28.7285 11.5981 28.7872 12.2183 28.3859L26.4731 18.9322C27.073 18.5114 27.0527 17.6991 26.5545 17.2685L26.5443 17.2783Z" fill="#ABCE4A"/>
                                            </svg>
                                            </button>';
                                    echo '<button type="button" class="license-verify-btn license-edit-btn hywd-plugin-info-popup left-popup" data-title="'.esc_attr($info_popup_text).'"><svg width="36" height="36" viewBox="0 0 36 36" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M7.94958 12.1145C8.59947 11.4646 9.48092 11.0995 10.4 11.0995H11.6361C12.1847 11.0995 12.6294 11.5442 12.6294 12.0928C12.6294 12.6414 12.1847 13.0861 11.6361 13.0861H10.4C10.0078 13.0861 9.63166 13.2419 9.35432 13.5192C9.07698 13.7966 8.92118 14.1727 8.92118 14.5649V25.6896C8.92118 26.0818 9.07698 26.4579 9.35432 26.7353C9.63166 27.0126 10.0078 27.1684 10.4 27.1684H21.5247C21.9169 27.1684 22.293 27.0126 22.5704 26.7353C22.8477 26.4579 23.0035 26.0818 23.0035 25.6896V24.4535C23.0035 23.9049 23.4482 23.4602 23.9968 23.4602C24.5454 23.4602 24.9901 23.9049 24.9901 24.4535V25.6896C24.9901 26.6087 24.625 27.4901 23.9751 28.14C23.3252 28.7899 22.4438 29.155 21.5247 29.155H10.4C9.48092 29.155 8.59947 28.7899 7.94958 28.14C7.29968 27.4901 6.93457 26.6087 6.93457 25.6896V14.5649C6.93457 13.6458 7.29968 12.7644 7.94958 12.1145Z" fill="#ABCE4A"/>
                                            <path fill-rule="evenodd" clip-rule="evenodd" d="M23.8082 7.20582C24.4812 6.53301 25.394 6.15503 26.3457 6.15503C27.2976 6.15503 28.2105 6.53317 28.8836 7.20628C29.5567 7.87938 29.9348 8.7923 29.9348 9.74422C29.9348 10.6958 29.557 11.6083 28.8844 12.2814C28.8841 12.2816 28.8839 12.2819 28.8836 12.2822L27.3044 13.867C27.2668 13.9252 27.2226 13.9803 27.1716 14.0312C27.1216 14.0812 27.0675 14.1248 27.0105 14.162L18.5204 22.6824C18.334 22.8695 18.0808 22.9746 17.8168 22.9746H14.1085C13.56 22.9746 13.1152 22.5299 13.1152 21.9813V18.2731C13.1152 18.0091 13.2204 17.7559 13.4074 17.5695L21.9279 9.0794C21.965 9.02236 22.0086 8.96829 22.0587 8.91824C22.1096 8.86731 22.1647 8.82307 22.2228 8.78551L23.8082 7.20582C23.8083 7.20567 23.808 7.20598 23.8082 7.20582ZM22.7752 11.0395L15.1018 18.6856V20.988H17.4043L25.0503 13.3146L22.7752 11.0395ZM26.4526 11.9074L24.1825 9.63732L25.2125 8.61102C25.513 8.31048 25.9206 8.14164 26.3457 8.14164C26.7707 8.14164 27.1783 8.31048 27.4788 8.61102C27.7794 8.91156 27.9482 9.31918 27.9482 9.74422C27.9482 10.1692 27.7794 10.5769 27.4788 10.8774L26.4526 11.9074Z" fill="#ABCE4A"/>
                                            </svg>
                                            </button>';
                                }

                                ?>
                                <input type="hidden" id="hywd-plugin-unique-id" value="<?php echo esc_attr($plugin['unique_id']); ?>">

                            </form>
                            <p class="hywd-license-response-success" id="hywd-response-success-<?php echo esc_attr($plugin['unique_id']); ?>">&nbsp;</p>
                            <p class="hywd-license-response-error" id="hywd-response-error-<?php echo esc_attr($plugin['unique_id']); ?>">&nbsp;</p>
                        </td>

                        <td class="hywd-plugin-switch">
                            <form class="hywd_switch_btn hywd_plugin_toggle">
                                <input type="checkbox" id="hywd_plugin_active_sts" class="hywd_plugin_active_sts" <?php echo esc_attr(checked('1', $plugin['active_status'])); ?>>
    <!--                            <button type="submit" id="hywd_toggle_switch" data-plugin="--><?php //echo esc_attr($plugin['main_file']); ?><!--">-->
                                <a href="<?php echo admin_url('plugins.php'); ?>" id="hywd_toggle_switch">
                                </a>
                            </form>
                        </td>

                        <td class="hywd-plugin-shop">
                            <div class="hywd-pm-btn-transparent">
                                <a href=" <?php echo esc_url($plugin['shop']); ?>" target="_blank" class="hywd-shop-link">View in Shop</a>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>


                </table>
            </div>
        </div>
        <?php
    }

    add_action('admin_post_save_hywd_plugins_info', 'save_hywd_plugins_info');
    add_action('admin_post_nopriv_save_hywd_plugins_info', 'save_hywd_plugins_info');
}

?>
