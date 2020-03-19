<?php

if (!defined('ABSPATH')) exit;
if (!class_exists('WPEInfo')) :
	class WPEInfo {
		public $settings;
		public $plugname = 'wpengine';
		public $brandname = 'WPEngine Migration';
		public $badgeinfo = 'wpebadge';
		public $ip_header_option = 'wpeipheader';
		public $brand_option = 'wpebrand';
		public $version = '3.4';
		public $webpage = 'https://wpengine.com';
		public $appurl = 'https://wpengine.blogvault.net';
		public $slug = 'wp-site-migrate/wpengine.php';
		public $plug_redirect = 'wperedirect';
		public $logo = '../assets/img/wpengine-logo.png';

		public function __construct($settings) {
			$this->settings = $settings;
		}

		public function getBrandInfo() {
			return $this->settings->getOption($this->brand_option);
		}

		public function getBrandName() {
			$brand = $this->getBrandInfo();
			if ($brand && array_key_exists('menuname', $brand)) {
				return $brand['menuname'];
			}
			return $this->brandname;
		}

		public function getMonitTime() {
			$time = $this->settings->getOption('bvmonittime');
			return ($time ? $time : 0);
		}

		public function appUrl() {
			if (defined('BV_APP_URL')) {
				return BV_APP_URL;
			} else {
				$brand = $this->getBrandInfo();
				if ($brand && array_key_exists('appurl', $brand)) {
					return $brand['appurl'];
				}
				return $this->appurl;
			}
		}
		
		public function isActivePlugin() {
			$expiry_time = time() - (3 * 24 * 3600);
			return ($this->getMonitTime() > $expiry_time);
		}

		public function isProtectModuleEnabled() {
			return ($this->settings->getOption('bvptplug') === $this->plugname) &&
				$this->isActivePlugin();
		}

		public function isDynSyncModuleEnabled() {
			return ($this->settings->getOption('bvdynplug') === $this->plugname) &&
				$this->isActivePlugin();
		}
		public function isActivateRedirectSet() {
			return ($this->settings->getOption($this->plug_redirect) === 'yes') ? true : false;
		}

		public function isMalcare() {
			return $this->getBrandName() === 'MalCare - Pro';
		}

		public function isBlogvault() {
			return $this->getBrandName() === 'BlogVault';
		}

		public function respInfo() {
			return array(
				"bvversion" => $this->version,
				"sha1" => "true"
			);
		}
	}
endif;