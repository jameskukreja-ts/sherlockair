<?php

if (function_exists('is_admin') && is_admin()) {

	add_action('admin_notices', 'blue_corona_rrp_page_create_admin_notice_error');
	add_action('admin_menu', 'blue_corona_rrp_page_create');
	function blue_corona_rrp_page_create() {

		add_menu_page(
			'Blue Corona RRP Admin Page',
			'Blue Corona RRP Settings',
			'edit_posts',
			BLUE_CORONA_RRP_PLUGIN_SETTINGS_ADMIN_URL_KEY,
			'blue_corona_rrp_settings_display',
			'',
			24
		);
	}

	function blue_corona_rrp_settings_display() {
		$delete_transient = 'delete_transient';
		if (is_multisite()) {
			$delete_transient = 'delete_site_transient';
		}
		$delete_transient(BLUE_CORONA_RRP_DOMAIN_CACHE_KEY);

		$api_url = get_blue_corona_rrp_api_url();
		$api_key = get_blue_corona_rrp_api_key();
		$skip_forms = get_blue_corona_rrp_skip_forms();
		$dev_mode = get_blue_corona_rrp_dev_mode();

		if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$is_valid = true;

			$api_url = $_POST['api_url'];
			$api_key = $_POST['api_key'];
			$skip_forms = $_POST['skip_forms'];
			$dev_mode = $_POST['dev_mode'];

			if (!is_rrp_api_key_valid($api_key)) {
				add_settings_error(
					'hasApiKeyError',
					'validationError',
					'API Key is required.',
					'error'
				);
				$is_valid = false;
			}

			if (!is_rrp_api_url_valid($api_url)) {
				add_settings_error(
					'hasApiUrlError',
					'validationError',
					'API URL is required and must be a valid URL.',
					'error'
				);
				$is_valid = false;
			}

			if ($is_valid) {

				?>
    <div class="updated notice">
        <p><?php _e('The settings have been updated.', 'my_plugin_textdomain');?></p>
    </div>
<?php

				update_option(BLUE_CORONA_RRP_PLUGIN_API_URL, rtrim($api_url, '/'));
				update_option(BLUE_CORONA_RRP_PLUGIN_API_KEY, $api_key);
				update_option(BLUE_CORONA_RRP_PLUGIN_SKIP_FORMS_KEY, $skip_forms);
				update_option(BLUE_CORONA_RRP_DEV_MODE, !empty($dev_mode));

			}
		}

		require_once 'settings-form.php';
	}
	//add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'ndn_plugin_action_links');

}