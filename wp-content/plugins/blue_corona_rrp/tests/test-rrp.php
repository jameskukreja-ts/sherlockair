<?php
/**
 * Class SampleTest
 *
 * @package Tester
 */

/**
 * Sample test case.
 */
//bash bin/install-wp-tests.sh test_blue_corona_rrp root '' 127.0.0.1 latest

class RrpTest extends WP_UnitTestCase {

	/** Activate the plugin, mock all the things */
	public function setUp() {
		/* Activate GravityForms */
		require_once dirname(dirname(__FILE__)) . '/../gravityforms/gravityforms.php';
		GFForms::setup(true);

		parent::setUp();

	}

	function get_exported_form() {
		$json_form = json_decode(file_get_contents(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'gravityforms-export-2017-02-03.json'), true);
		print_r($json_form[0]);
	}

	function get_form() {

		return GFAPI::add_form(
			array(
				'title' => 'Contact',
				'fields' => array(
					array(
						'id' => 1,
						'label' => 'Name',
						'adminLabel' => '',
						'type' => 'name',
						'isRequired' => true,
						'inputs' => array(
							array(
								'id' => 1.3,
								'label' => 'First',
								'name' => '',
							),
							array(
								'id' => 1.6,
								'label' => 'Last',
								'name' => '',
							),
						),
					),
					array(
						'id' => 2,
						'label' => 'Email',
						'adminLabel' => '',
						'type' => 'email',
						'isRequired' => true,
					),
					array(
						'id' => 3,
						'label' => 'Subject',
						'adminLabel' => '',
						'type' => 'text',
						'isRequired' => true,
					),
					array(
						'id' => 4,
						'label' => 'Message',
						'adminLabel' => '',
						'type' => 'textarea',
						'isRequired' => true,
					),
				),
				'is_active' => '1',
				'date_created' => date('Y-m-d H:i:s'),
				'confirmation' => array(),
			)
		);
	}

	function test_uuid() {
		$this->assertTrue(is_string(blue_corona_rrp_gen_uuid()));
	}

	function test_blue_corona_rrp_gform_pre_send_email() {

		//get a coded message
		$json = array('test' => 'test');
		$blue_corona_rrp_build_coded_json = blue_corona_rrp_build_coded_json($json);
		$this->assertEquals($blue_corona_rrp_build_coded_json, BLUE_CORONA_RRP_PLUGIN_BOUNDARY_UUID .
			str_replace(
				'}',
				BLUE_CORONA_RRP_PLUGIN_JSON_CLOSE_HACK_UUID,
				str_replace(
					'{',
					BLUE_CORONA_RRP_PLUGIN_JSON_OPEN_HACK_UUID,
					json_encode($json)
				)
			) . BLUE_CORONA_RRP_PLUGIN_BOUNDARY_UUID);

		//test html
		$email = blue_corona_rrp_gform_pre_send_email(
			array(
				'message' =>
				'Test' . $blue_corona_rrp_build_coded_json,
			),
			'html');

		$this->assertEquals($email['message'], 'Test');
		//test text
		$email = blue_corona_rrp_gform_pre_send_email(
			array(
				'message' =>
				'Test' . $blue_corona_rrp_build_coded_json,
			),
			'text');

		$this->assertEquals($email['message'], 'Test');

		$parts = explode(BLUE_CORONA_RRP_PLUGIN_BOUNDARY_UUID, $blue_corona_rrp_build_coded_json);
		$this->assertEquals('{"test":"test"}', blue_corona_rrp_build_decoded_json($parts[1]));
	}

	function test_blue_corona_rrp_get_sourcebuster_cookies() {
		$sbjs_cookies = blue_corona_rrp_get_sourcebuster_cookies(
			array(
				'test' => 1,
				'test2' => 2,
				'sbjs_add' => 3,
				'sbjs_current' => 4,
			)
		);
		$this->assertEquals(
			$sbjs_cookies,
			array(
				'sbjs_add' => 3,
				'sbjs_current' => 4,
			)
		);
	}

	function test_blue_corona_rrp_notification_signature() {
		$form_id = $this->get_form();
		//$this->get_exported_form();
		$this->assertFalse(is_wp_error($form_id));

		$entry_id = GFAPI::add_entry(
			array(
				'form_id' => $form_id,
				'1.3' => 'First',
				'1.6' => 'Last',
				'2' => 'test@coretechs.com',
				'3' => 'This is Subject',
				'4' => 'This is Message',
				'date_created' => date('Y-m-d G:i'),
				999 => 'user_id|1',
			)
		);
		$this->assertFalse(is_wp_error($entry_id));
		$notification = blue_corona_rrp_notification_signature(
			array('message' => 'test'),
			GFAPI::get_form($form_id),
			GFAPI::get_entry($entry_id)
		);

		//Make sure our json is in there

		$this->assertEquals(
			$notification['json'], array(
				'values' => array(
					'Name' => 'First Last',
					'Email' => 'test@coretechs.com',
					'Subject' => 'This is Subject',
					'Message' => 'This is Message',
				),
				'cookies' => array(),
			)
		);

	}

	function test_is_api_settings_valid() {
		//I am not sure how to get the activation hook to run automatically
		rrp_default_options();
		$this->assertEquals(DEFAULT_BLUE_CORONA_RRP_PLUGIN_API_URL, get_option(BLUE_CORONA_RRP_PLUGIN_API_URL));
		$this->assertFalse(is_rrp_api_key_valid(''));
		$this->assertTrue(is_rrp_api_key_valid('134'));
		$this->assertTrue(is_rrp_api_key_valid('124345dfgddfdf'));
		$this->assertFalse(is_rrp_api_key_valid('124345dfgddfdf!'));
		$this->assertFalse(is_rrp_api_url_valid(''));
		$this->assertFalse(is_rrp_api_url_valid('134'));
		$this->assertTrue(is_rrp_api_url_valid('http://localhost:8000'));

	}

	function test_blue_corona_rrp_get_source_medium() {
		$source_and_medium_message = blue_corona_rrp_get_source_medium(
			array(
				'test' => 1,
				'test2' => 2,
				'sbjs_add' => 3,
				'sbjs_current' => 'typ=organic|||src=google|||mdm=organic|||cmp=(none)|||cnt=(none)|||trm=(none)',
			)
		);
		$this->assertEquals(
			$source_and_medium_message,
			" <br><br>Source: google <br>Medium: organic"
		);
	}

    function test_blue_corona_rrp_get_source_medium_values() {
        $source_and_medium_values = blue_corona_rrp_get_source_medium_values(
            array(
                'typ' => 'organic',
                'src' => 'google',
                'mdm' => 'organic',
                'cmp' => '(none)',
                'cnt' => '(none)',
                'trm' => '(none)',
            )
        );
        $this->assertEquals(
            $source_and_medium_values,
            array(
                'typ' => 'organic',
                'src' => 'google',
                'mdm' => 'organic',
                'cmp' => '(none)',
                'cnt' => '(none)',
                'trm' => '(none)',
            )
        );
    }

    function test_blue_corona_rrp_get_source() {
	    $source = blue_corona_rrp_get_source();
        // Need to write the test to check if a string was returned
    }

    function test_blue_corona_rrp_get_medium() {
	    $medium = blue_corona_rrp_get_medium();
        // Need to write the test to check if a string was returned
    }

}

