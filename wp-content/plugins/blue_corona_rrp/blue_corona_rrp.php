<?php
/*
Plugin Name: Blue Corona RRP
Plugin URI: https://www.bluecorona.com
Description: Allows web leads to be captured in the Blue Corona reporting platform.
Author: Blue Corona
Author URI: https://www.bluecorona.com
Version: 2.0
 */
/*
Also make changes in the JSON file: reporting/website/static/blue_corona_rrp.json
to run tests:
cd to plugin directory
bash bin/install-wp-tests.sh reporting_php7_test root '' 127.0.0.1 latest
phpunit
 */
//Add the scripts
define('RRP_VERSION', '2.0');
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Promise as GuzzlePromise;
require_once 'vendor/autoload.php';

//start the session
function rrp_register_session() {
	if (!session_id()) {
		session_start();
	}

	if (empty($_SESSION['rrp_submissions'])) {
		$_SESSION['rrp_submissions'] = array();
	}

}
add_action('init', 'rrp_register_session');

//Update checker
Puc_v4_Factory::buildUpdateChecker(
	'https://reports.bluecorona.com/static/blue_corona_rrp.json',
	__FILE__,
	'blue_corona_rrp'
);

function blue_corona_rrp_enqueue_sourcebuster() {
	wp_enqueue_script(
		'rrp',
		plugins_url('/js/dist/bundle.min.js#asyncload', __FILE__),
		array(),
		WP_DEBUG ? sha1_file(
			dirname(__FILE__) . DIRECTORY_SEPARATOR . 'js/dist/bundle.min.js'
		) : RRP_VERSION,
		false
	);

	$nonce = wp_create_nonce('wp_rest');
	$urls = array();
	foreach (rrp_domains() as $domain) {
		$urls[] = implode(
			'/',
			array(
				$domain,
				'api',
				get_blue_corona_rrp_api_key(),
				'chat/create/',
			)
		);
	}

	$urls = array_unique($urls);
	wp_localize_script(
		'rrp',
		'rrp_settings',
		array(
			'url' => get_blue_corona_rrp_api_url(),
			'key' => get_blue_corona_rrp_api_key(),
			'chat_url' => admin_url('admin-ajax.php') . "?action=rrp_chat_handler",
			'chat_api_urls' => $urls,
		)
	);
}
add_action('wp_enqueue_scripts', 'blue_corona_rrp_enqueue_sourcebuster');

function blue_corona_rrp_enqueue_admin_scripts() {
	add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'add_rrp_action_links');

}
add_action('admin_enqueue_scripts', 'blue_corona_rrp_enqueue_admin_scripts');

//names for the options
define('BLUE_CORONA_RRP_PLUGIN_API_URL', 'BLUE_CORONA_RRP_PLUGIN_API_URL');
define('DEFAULT_BLUE_CORONA_RRP_PLUGIN_API_URL', 'https://reports.bluecorona.com');
define('BLUE_CORONA_RRP_PLUGIN_API_KEY', 'BLUE_CORONA_RRP_PLUGIN_API_KEY');
define('BLUE_CORONA_RRP_PLUGIN_SKIP_FORMS_KEY', 'BLUE_CORONA_RRP_PLUGIN_SKIP_FORMS_KEY');
define('BLUE_CORONA_RRP_DEV_MODE', 'BLUE_CORONA_RRP_DEV_MODE');
define('BLUE_CORONA_RRP_PLUGIN_SETTINGS_ADMIN_URL_KEY', 'blue_corona_rrp_settings');
define('BLUE_CORONA_RRP_PLUGIN_BOUNDARY_UUID', blue_corona_rrp_gen_uuid());
define('BLUE_CORONA_RRP_PLUGIN_JSON_OPEN_HACK_UUID', blue_corona_rrp_gen_uuid());
define('BLUE_CORONA_RRP_PLUGIN_JSON_CLOSE_HACK_UUID', blue_corona_rrp_gen_uuid());
define('BLUE_CORONA_RRP_DOMAIN_CACHE_KEY', 'rrp_domains');

//Start Set default host
register_activation_hook(__FILE__, 'rrp_default_options');

// Set default values here
function rrp_default_options() {
	if (empty(get_blue_corona_rrp_api_url())) {
		update_option(BLUE_CORONA_RRP_PLUGIN_API_URL, DEFAULT_BLUE_CORONA_RRP_PLUGIN_API_URL);
	}
}

//End Set default host

//logging function
if (!function_exists('write_log')) {
	function write_log($log) {
		if (true === WP_DEBUG) {
			if (is_array($log) || is_object($log)) {
				error_log(print_r($log, true));
			} else {
				error_log($log);
			}
		}
	}
}

function add_rrp_action_links($links) {
	$mylinks = array(
		'<a href="' . admin_url('admin.php?page=' . BLUE_CORONA_RRP_PLUGIN_SETTINGS_ADMIN_URL_KEY) . '">Settings</a>',
	);
	return array_merge($links, $mylinks);
}

function get_blue_corona_rrp_settings_url() {
	return admin_url('admin.php?page=' . BLUE_CORONA_RRP_PLUGIN_SETTINGS_ADMIN_URL_KEY);
}

function get_blue_corona_rrp_api_url() {
	return get_option(BLUE_CORONA_RRP_PLUGIN_API_URL);
}

function get_blue_corona_rrp_api_key() {
	return get_option(BLUE_CORONA_RRP_PLUGIN_API_KEY);
}
function get_blue_corona_rrp_dev_mode() {
	return get_option(BLUE_CORONA_RRP_DEV_MODE);
}
function get_blue_corona_rrp_skip_forms() {
	$return = get_option(BLUE_CORONA_RRP_PLUGIN_SKIP_FORMS_KEY);
	if (!is_array($return)) {
		$return = array();
	}
	return $return;
}

function is_rrp_api_key_valid($api_key) {
	return !empty($api_key) && ctype_alnum($api_key);
}

function is_rrp_api_url_valid($api_url) {
	return !empty($api_url) && filter_var($api_url, FILTER_VALIDATE_URL);
}

function is_api_url_responding($api_url) {
	$is_api_url_responding = false;
	try {

		$client = new GuzzleClient(['timeout' => 6]);

		$response = $client->request(
			'POST',
			implode(
				'/',
				array(
					$api_url,
					'api',
					'ok/',
				)
			)
		);
		$is_api_url_responding = $response->getStatusCode() == 200;

	} catch (Exception $e) {
		write_log($e->getMessage());
		$is_api_url_responding = false;
	}
	return $is_api_url_responding;
}

function blue_corona_rrp_gen_uuid() {
	return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
		// 32 bits for "time_low"
		mt_rand(0, 0xffff), mt_rand(0, 0xffff),

		// 16 bits for "time_mid"
		mt_rand(0, 0xffff),

		// 16 bits for "time_hi_and_version",
		// four most significant bits holds version number 4
		mt_rand(0, 0x0fff) | 0x4000,

		// 16 bits, 8 bits for "clk_seq_hi_res",
		// 8 bits for "clk_seq_low",
		// two most significant bits holds zero and one for variant DCE1.1
		mt_rand(0, 0x3fff) | 0x8000,

		// 48 bits for "node"
		mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
	);
}

//Check our settings
function blue_corona_rrp_page_create_admin_notice_error() {
	$class = 'notice notice-error';

	if (!is_rrp_api_key_valid(get_blue_corona_rrp_api_key())) {
		printf('<div class="%1$s"><p>%2$s</p></div>', $class, 'Blue Corona RRP API Key does not appear to be set correctly. <a href="' . get_blue_corona_rrp_settings_url() . '">Click here to set it</a>');
	}
	if (!is_rrp_api_url_valid(get_blue_corona_rrp_api_url())) {
		printf('<div class="%1$s"><p>%2$s</p></div>', $class, 'Blue Corona RRP API URL does not appear to be set correctly. <a href="' . get_blue_corona_rrp_settings_url() . '">Click here to set it</a>');
	} else {
		//Check to see if site is repsonding
		//notice-warning

		if (!is_api_url_responding(get_blue_corona_rrp_api_url())) {
			printf('<div class="%1$s"><p>Blue Corona RRP API URL: %2$s does not appear to be responding.</p></div>', $class, get_blue_corona_rrp_api_url());
		}

	}
}

function blue_corona_rrp_gform_pre_send_email($email, $message_format, $notification) {
	$parts = explode(BLUE_CORONA_RRP_PLUGIN_BOUNDARY_UUID, $email['message']);
	$email['message'] = $parts[0];

	//send the message
	//Only send if it is not in the skip
	if (!in_array($notification['form_id'], get_blue_corona_rrp_skip_forms())) {
		blue_corona_rrp_send_data(
			array(
				'json' => blue_corona_rrp_build_decoded_json($parts[1]),
				'message' => $email['message'],
			)
		);
	}

	return $email;
}

add_filter('gform_pre_send_email', 'blue_corona_rrp_gform_pre_send_email', 10, 3);

function blue_corona_rrp_notification_signature($notification, $form, $entry) {

	//log the cookies
	//write_log('blue_corona_rrp_notification_signature cookies: ' . print_r($_COOKIE, true));

	$entry_array = (array) $entry;
	$json = array(
		'values' => array_merge($_POST, array('form_id' => $form['id'])),
		'cookies' => blue_corona_rrp_get_sourcebuster_cookies($_COOKIE),
		'entry' => $entry_array['id'],
	);
	foreach ($form['fields'] as $field) {
		$json['values'][$field['label']] = $field->get_value_export($entry); //$field->get_value_export($entry, $field['id'], true);
	}
	$notification['json'] = $json;
	//wp_die($notification);
	//log the json
	//write_log('blue_corona_rrp_notification_signature json: ' . print_r($notification['json'], true));

	$notification['message'] = $notification['message'] . blue_corona_rrp_get_source_medium($json['cookies'], $notification['toType']) . blue_corona_rrp_build_coded_json($json);
	//store form_id so we can determine if we should skip it.
	//new versions of gravity forms pass this to the gform_pre_send_email filter but we need more flexibilty
	$notification['form_id'] = $form['id'];
	return $notification;

}

add_filter('gform_notification', 'blue_corona_rrp_notification_signature', 10, 3);

function rrp_domains() {
	$get_transient = 'get_transient';
	$set_transient = 'set_transient';
	if (is_multisite()) {
		$get_transient = 'get_site_transient';
		$set_transient = 'set_site_transient';
	}

	$domains = $get_transient(BLUE_CORONA_RRP_DOMAIN_CACHE_KEY);
	if ($domains === false) {

		$domains = array(
			get_blue_corona_rrp_api_url(),
		);

		//build a list of dev domains
		foreach (
			array(
				'https://dev-reports.bluecorona.com',
				'https://dev-rrp-backup.bluecoronalab.com',
			) as $domain
		) {
			if (is_api_url_responding($domain)) {
				$domains[] = $domain;
				break;
			}
		}

		//build a list of prod domains
		foreach (
			array(
				'https://reports.bluecorona.com',
				'https://rrp-backup.bluecoronalab.com',
			) as $domain
		) {
			if (is_api_url_responding($domain)) {
				$domains[] = $domain;
				break;
			}
		}

		$domains = array_unique($domains);
		if (get_blue_corona_rrp_dev_mode()) {
			$domains = array_filter($domains, function ($domain) {
				if (stripos($domain, 'dev-') === false && stripos($domain, 'localhost') === false) {
					return false;
				}

				return true;
			});
		}
		write_log($domains);
		$set_transient(BLUE_CORONA_RRP_DOMAIN_CACHE_KEY, $domains, 60 * 5);
	}
	return $domains;
}

function blue_corona_rrp_send_data($notification) {
	if (!is_rrp_api_key_valid(get_blue_corona_rrp_api_key()) || !is_rrp_api_url_valid(get_blue_corona_rrp_api_url())) {
		//cant go on
		return False;
	}

	//make sure we arent sending a duplicate
	$entry_id = json_decode($notification['json'])->entry;

	if (in_array($entry_id, $_SESSION['rrp_submissions'])) {
		return False;
	}

	$_SESSION['rrp_submissions'][] = $entry_id;

	try {
		$urls = array();
		foreach (rrp_domains() as $domain) {
			$urls[] = implode(
				'/',
				array(
					$domain,
					'api',
					get_blue_corona_rrp_api_key(),
					'create/',
				)
			);
		}

		$urls = array_unique($urls);
		$client = new GuzzleClient(['timeout' => 6]);

		$promises = array();
		foreach ($urls as $url) {
			$promises[] = $client->postAsync(
				$url,
				[
					'form_params' => [
						'json' => $notification['json'],
						'body_plain' => strip_tags($notification['message']),
					],
				]
			);
		}
		$results = GuzzlePromise\settle($promises)->wait();

		foreach ($results as $result) {

			if ($result['state'] === 'fulfilled') {
				$response = $result['value'];
				if ($response->getStatusCode() != 201) {
					write_log('ERR: ' . $response->getStatusCode());
				}
			} else if ($result['state'] === 'rejected') {
				// notice that if call fails guzzle returns is as state rejected with a reason.
				write_log('ERR: ' . $result['reason']);
			} else {
				write_log('Crawler FetchHomePages: unknown fetch fail domain');
			}

		}

	} catch (Exception $e) {
		echo '<!-- Caught exception: ', $e->getMessage(), '-->';
		write_log($e->getMessage());
	}
	return True;
}

function blue_corona_rrp_build_coded_json($json) {
	return BLUE_CORONA_RRP_PLUGIN_BOUNDARY_UUID .
	str_replace(
		'}',
		BLUE_CORONA_RRP_PLUGIN_JSON_CLOSE_HACK_UUID,
		str_replace(
			'{',
			BLUE_CORONA_RRP_PLUGIN_JSON_OPEN_HACK_UUID,
			json_encode($json)
		)
	) . BLUE_CORONA_RRP_PLUGIN_BOUNDARY_UUID;
}

function blue_corona_rrp_build_decoded_json($json) {
	$json = str_replace(BLUE_CORONA_RRP_PLUGIN_JSON_OPEN_HACK_UUID, '{', $json);
	$json = str_replace(BLUE_CORONA_RRP_PLUGIN_JSON_CLOSE_HACK_UUID, '}', $json);

	return $json;
}

function blue_corona_rrp_get_sourcebuster_cookies($cookies) {
	return array_intersect_key(
		$cookies,
		array_flip(
			preg_grep(
				'/^sbjs/i',
				array_keys($cookies)
			)
		)
	);
}

function blue_corona_rrp_get_source_medium($json, $toType) {
	//Leave early
	if (empty($json['sbjs_current'])) {
		return '';
	}

	$return = '';
	$sbjs_current_cookie_parts = array();
	foreach (explode('|||', $json['sbjs_current']) as $temp) {
		$temp_parts = explode('=', $temp);
		if (!empty($temp_parts[1])) {
			$sbjs_current_cookie_parts[$temp_parts[0]] = $temp_parts[1];
		}
	}
	if (!empty($sbjs_current_cookie_parts["src"]) && $toType == "email") {
		$return .= " <br><br>Source: " . $sbjs_current_cookie_parts["src"];
	}
	if (!empty($sbjs_current_cookie_parts["mdm"]) && $toType == "email") {
		$return .= " <br>Medium: " . $sbjs_current_cookie_parts["mdm"];
	}
	return $return;
}

function blue_corona_rrp_get_source_medium_values($json) {
	//Leave early
	if (empty($json['sbjs_current'])) {
		return '';
	}
	$return = '';
	$sbjs_current_cookie_parts = array();
	foreach (explode('|||', $json['sbjs_current']) as $temp) {
		$temp_parts = explode('=', $temp);
		if (!empty($temp_parts[1])) {
			$sbjs_current_cookie_parts[$temp_parts[0]] = $temp_parts[1];
		}
	}
	if (!empty($sbjs_current_cookie_parts)) {
		return $sbjs_current_cookie_parts;
	}
	return $return;
}

// Add Source, Medium columns to entries list for Gravity Forms
add_filter('gform_entry_meta', 'rrp_custom_entry_meta', 10, 2);
function rrp_custom_entry_meta($entry_meta, $form_id) {
	//data will be stored with the meta key named score
	//label - entry list will use Score as the column header
	//is_numeric - used when sorting the entry list, indicates whether the data should be treated as numeric when sorting
	//is_default_column - when set to true automatically adds the column to the entry list, without having to edit and add the column for display
	//update_entry_meta_callback - indicates what function to call to update the entry meta upon form submission or editing an entry
	$entry_meta['source'] = array(
		'label' => 'Source',
		'is_numeric' => false,
		'update_entry_meta_callback' => 'blue_corona_rrp_get_source',
		'is_default_column' => true,
	);
	//data will be stored with the meta key named test
	$entry_meta['medium'] = array(
		'label' => 'Medium',
		'is_numeric' => false,
		'update_entry_meta_callback' => 'blue_corona_rrp_get_medium',
		'is_default_column' => true,
	);
	return $entry_meta;
}

function blue_corona_rrp_get_source() {
	$return = '';
	$json = array(
		'cookies' => blue_corona_rrp_get_sourcebuster_cookies($_COOKIE),
	);
	$source_medium = blue_corona_rrp_get_source_medium_values($json['cookies']);
	if (!empty($source_medium)) {
		return $source_medium["src"];
	}
	return $return;
}

function blue_corona_rrp_get_medium() {
	$return = '';
	$json = array(
		'cookies' => blue_corona_rrp_get_sourcebuster_cookies($_COOKIE),
	);
	$source_medium = blue_corona_rrp_get_source_medium_values($json['cookies']);
	if (!empty($source_medium)) {
		return $source_medium["mdm"];
	}
	return $return;
}

add_action('wp_footer', 'blue_corona_rrp_footer_scripts');
function blue_corona_rrp_footer_scripts() {
	?>
<script></script>
<?php
}

add_filter('clean_url', function ($url) {
	if (strpos($url, '#asyncload') === false) {
		return $url;
	} else if (is_admin()) {
		return str_replace('#asyncload', '', $url);
	} else {
		return str_replace('#asyncload', '', $url) . "' async='async";
	}
}, 11, 1);

//Add chat handler
add_action('wp_ajax_nopriv_rrp_chat_handler', 'rrp_chat_handler');
add_action('wp_ajax_rrp_chat_handler', 'rrp_chat_handler');
function rrp_chat_handler() {
	$data = json_decode(
		file_get_contents('php://input'),
		TRUE
	); //convert JSON into array

	if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
		wp_send_json_error(
			array(
				'error' => "json data is incorrect",
			)
		);

	}

	$return = array();

	if (empty($data['chat_id'])) {
		wp_send_json_error(
			array(
				'error' => 'chat_id is empty',
				'data' => $data,
			)
		);
	}
	try {
		$urls = array();
		foreach (rrp_domains() as $domain) {
			$urls[] = implode(
				'/',
				array(
					$domain,
					'api',
					get_blue_corona_rrp_api_key(),
					'chat/create/',
				)
			);
		}

		$urls = array_unique($urls);
		$client = new GuzzleClient(['timeout' => 6]);

		$promises = array();
		foreach ($urls as $url) {
			$promises[] = $client->postAsync(
				$url,
				[
					'json' => [
						'json' => json_encode(
							array(
								'cookies' => blue_corona_rrp_get_sourcebuster_cookies($_COOKIE),
							)
						),
						'chat_id' => $data['chat_id'],
					],
				]
			);
		}
		$results = GuzzlePromise\settle($promises)->wait();

		foreach ($results as $result) {

			if ($result['state'] === 'fulfilled') {
				$response = $result['value'];
				if ($response->getStatusCode() != 201) {
					write_log('ERR: ' . $response->getStatusCode());
				}
			} else if ($result['state'] === 'rejected') {
				// notice that if call fails guzzle returns is as state rejected with a reason.
				write_log('ERR: ' . $result['reason']);
			} else {
				write_log('Crawler FetchHomePages: unknown fetch fail domain');
			}

		}
		wp_send_json_success($return);

	} catch (Exception $e) {
		write_log($e->getMessage());
		wp_send_json_error(array('error' => $e->getMessage()));
	}

}

//custom updates/upgrades
require_once dirname(__FILE__) . '/settings.php';
