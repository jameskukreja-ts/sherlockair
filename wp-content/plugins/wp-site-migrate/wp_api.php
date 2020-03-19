<?php

if (!defined('ABSPATH')) exit;
if (!class_exists('WPEWPAPI')) :
	class WPEWPAPI {
		public $settings;

		public function __construct($settings) {
			$this->settings = $settings;
		}
		
		public function pingbv($method, $body, $public = false) {
			if ($public) {
				$this->create_request_params($method, $public);
			} else {
				$accounts = WPEAccount::allAccounts($this->settings);
				foreach ($accounts as $pubkey => $value ) {
					$this->create_request_params($method, $pubkey);
				}
			}
		}

		public function create_request_params($method, $pubkey) {
			$account = WPEAccount::find($this->settings, $pubkey);
			$url = $account->authenticatedUrl($method);
			$this->http_request($url, $body);
		}

		public function http_request($url, $body) {
			$_body = array(
				'method' => 'POST',
				'timeout' => 15,
				'body' => $body);

			return wp_remote_post($url, $_body);
		}
	}
endif;