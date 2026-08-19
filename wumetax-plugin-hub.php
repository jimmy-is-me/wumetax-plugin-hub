<?php
/**
 * Plugin Name: Wumetax Plugin Hub
 * Description: Wumetax LTD 外掛市集、系統資訊與功能外掛管理中心。
 * Version: 0.1.0
 * Author: Wumetax LTD
 * Author URI: https://wumetax.com/
 * License: GPL-2.0-or-later
 * Text Domain: wumetax-plugin-hub
 */

defined('ABSPATH') || exit;

define('WUMETAX_HUB_VERSION', '0.1.0');

final class Wumetax_Plugin_Hub {
    public function __construct() {
        add_action('admin_menu', array($this, 'register_menu'), 20);
        add_filter('plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_settings_link'));
        add_action('admin_post_wumetax_hub_install', array($this, 'install_plugin'));
    }

    public function register_menu() {
        add_menu_page('Wumetax Plugin Hub', 'Wumetax Hub', 'manage_options', 'wumetax-plugin-hub', array($this, 'render_marketplace'), 'dashicons-screenoptions', 56);
        add_submenu_page('wumetax-plugin-hub', '外掛市集', '外掛市集', 'manage_options', 'wumetax-plugin-hub', array($this, 'render_marketplace'));
        add_submenu_page('wumetax-plugin-hub', '系統資訊', '系統資訊', 'manage_options', 'wumetax-system-info', array($this, 'render_system_info'));
        add_submenu_page('wumetax-plugin-hub', '聯絡我們', '聯絡我們', 'manage_options', 'wumetax-contact', array($this, 'render_contact'));
    }

    public function add_settings_link($links) {
        array_unshift($links, '<a href="' . esc_url(admin_url('admin.php?page=wumetax-plugin-hub')) . '">開啟 Hub</a>');
        return $links;
    }

    private function get_wumetax_plugins() {
        if (!function_exists('get_plugins')) require_once ABSPATH . 'wp-admin/includes/plugin.php';
        $plugins = array();
        foreach (get_plugins() as $file => $data) {
            $slug = dirname($file);
            if ($slug === 'wumetax-plugin-hub' || strpos($slug, 'wumetax-') === 0 || stripos($data['Name'], 'Wumetax') === 0) {
                $plugins[] = array('name' => $data['Name'], 'version' => $data['Version'], 'file' => $file, 'active' => is_plugin_active($file));
            }
        }
        return $plugins;
    }

    private function get_catalog() {
        $url = 'https://raw.githubusercontent.com/jimmy-is-me/wumetax-plugin-hub/main/catalog.json';
        $cached = get_transient('wumetax_hub_catalog');
        if ($cached !== false) return $cached;
        $response = wp_remote_get($url, array('timeout' => 10));
        $catalog = !is_wp_error($response) ? json_decode(wp_remote_retrieve_body($response), true) : array();
        set_transient('wumetax_hub_catalog', $catalog, 5 * MINUTE_IN_SECONDS);
        return $catalog;
    }

    public function install_plugin() {
        if (!current_user_can('install_plugins')) wp_die('權限不足');
        check_admin_referer('wumetax_hub_install');
        $repo = sanitize_text_field($_GET['repo'] ?? '');
        if ($repo !== 'jimmy-is-me/wumetax-webp-tools') wp_die('無效的外掛來源');
        require_once ABSPATH . 'wp-admin/includes/class-plugin-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';
        $upgrader = new Plugin_Upgrader(new Automatic_Upgrader_Skin());
        $upgrader->install('https://github.com/' . $repo . '/releases/latest/download/wumetax-webp-tools.zip');
        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }

    public function render_marketplace() {
        if (!current_user_can('manage_options')) wp_die('權限不足');
        $plugins = $this->get_wumetax_plugins();
        ?>
        <div class="wrap">
            <h1>Wumetax 外掛市集</h1>
            <p>由 Wumetax LTD 開發與維護。每個功能皆為可獨立安裝、啟用與更新的 WordPress 外掛。</p>
            <p><a class="button button-primary" href="<?php echo esc_url(admin_url('plugin-install.php?tab=upload')); ?>">上傳並安裝 Wumetax 外掛</a> <a class="button" href="https://wumetax.com/contact-us/" target="_blank" rel="noopener">聯絡我們</a></p>
            <h2>可安裝的 Wumetax 外掛</h2>
            <?php $catalog = $this->get_catalog(); foreach (($catalog['plugins'] ?? array()) as $item) : $install_url = wp_nonce_url(admin_url('admin-post.php?action=wumetax_hub_install&repo=' . rawurlencode($item['repository'])), 'wumetax_hub_install'); ?>
            <div class="card" style="max-width:800px"><h3><?php echo esc_html($item['name']); ?></h3><p><?php echo esc_html($item['description']); ?></p><p><a class="button button-primary" href="<?php echo esc_url($install_url); ?>">從 GitHub 安裝／更新</a> <a class="button" target="_blank" rel="noopener" href="<?php echo esc_url('https://github.com/' . $item['repository'] . '/releases'); ?>">版本資訊</a></p></div>
            <?php endforeach; ?>
            <h2>已安裝的 Wumetax 外掛</h2>
            <table class="widefat striped"><thead><tr><th>外掛</th><th>版本</th><th>狀態</th><th>操作</th></tr></thead><tbody>
            <?php if (empty($plugins)) : ?><tr><td colspan="4">尚未安裝其他 Wumetax 功能外掛。請使用上方按鈕安裝 ZIP。</td></tr>
            <?php else : foreach ($plugins as $plugin) : ?>
            <tr><td><?php echo esc_html($plugin['name']); ?></td><td><?php echo esc_html($plugin['version']); ?></td><td><?php echo $plugin['active'] ? '<span style="color:#00a32a">已啟用</span>' : '未啟用'; ?></td><td><a href="<?php echo esc_url(admin_url('plugins.php')); ?>">管理外掛</a></td></tr>
            <?php endforeach; endif; ?></tbody></table>
            <h2>即將提供</h2><p>WebP Tools 已作為第一個獨立功能外掛建立；後續會依相同結構逐步拆出維護模式、登入限制、郵件追蹤等功能。</p>
        </div><?php
    }

    public function render_system_info() {
        if (!current_user_can('manage_options')) wp_die('權限不足');
        global $wpdb;
        $theme = wp_get_theme();
        ?>
        <div class="wrap"><h1>系統資訊</h1><table class="widefat striped" style="max-width:800px"><tbody>
        <tr><th>品牌</th><td>Wumetax LTD</td></tr><tr><th>WordPress</th><td><?php echo esc_html(get_bloginfo('version')); ?></td></tr><tr><th>PHP</th><td><?php echo esc_html(PHP_VERSION); ?></td></tr><tr><th>MySQL</th><td><?php echo esc_html($wpdb->db_version()); ?></td></tr><tr><th>主題</th><td><?php echo esc_html($theme->get('Name')); ?></td></tr><tr><th>記憶體上限</th><td><?php echo esc_html(ini_get('memory_limit')); ?></td></tr><tr><th>網站時區</th><td><?php echo esc_html(wp_timezone_string()); ?></td></tr></tbody></table></div><?php
    }

    public function render_contact() {
        if (!current_user_can('manage_options')) wp_die('權限不足');
        echo '<div class="wrap"><h1>聯絡 Wumetax LTD</h1><p>需要協助或有功能建議，請前往 <a href="https://wumetax.com/contact-us/" target="_blank" rel="noopener">Wumetax 聯絡我們</a>。</p></div>';
    }
}
new Wumetax_Plugin_Hub();
