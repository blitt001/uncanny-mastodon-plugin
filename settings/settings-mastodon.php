<?php

/**
 * Mastodon Integration Settings
 */
class Mastodon_Integration_Settings extends \Uncanny_Automator\Settings\Premium_Integration_Settings {

	public $helpers;
	private $api_key;
	private $api_url;
	private $is_connected;

	public function set_properties() {

		// The unique page ID that will be added to the URL
		$this->set_id( 'mastodon' );

		// The integration icon will be used for the settings page, so set this option to the integration ID
		$this->set_icon( 'MASTODON' );

		// The name of the settings tab
		$this->set_name( 'Mastodon' );

		// Use this method to register an option for each field your settings page will have
		$this->register_option( 'mastodon_api_key' );
		$this->register_option( 'mastodon_url');

		// Handle the disconnect button action
		add_action( 'init', array( $this, 'disconnect' ) );

	}

	public function get_status() {

		// Not connected by default
		$this->is_connected = false;

		// Get the API key
		$this->api_key = get_option( 'mastodon_api_key', false );
		$this->api_url = get_option( 'mastodon_url', false );
		
		// If there is an API key, we are connected
		if ( false !== $this->api_key ) {
            if ( false !== $this->api_url ) {

				if ( $this->validate_settings($this->api_url, $this->api_key ) == 0) {

			    // Store the connected status for later use
			    	$this->is_connected = true;

			    // Return this string to show the green checkmark
			    	return 'success';
				}
            }
        }

		// Return an empty string if not connected
		return '';
	}

	/**
	 * Creates the output of the settings page
	 */
	public function output_panel_content() {

		// If the integration is not connected, output the field
		if ( ! $this->is_connected ) {

			$args = array(
				'id'       => 'mastodon_api_key',
				'value'    => $this->api_key,
				'label'    => 'API key',
				'required' => true,
			);

			$this->text_input( $args );

            $args = array(
				'id'       => 'mastodon_url',
				'value'    => $this->api_url,
				'label'    => 'Mastodon URL',
				'required' => true,
			);

			$this->text_input( $args );

		} else { // If the integration is connected, output the success message
			$this->add_alert(
				array(
					'type'    => 'success',
					'heading' => __( 'You are connected to Mastodon', 'automator-mastodon' ),
					'content'  => 'Username: ' . $this->get_mastodon_account_name($this->api_url, $this->api_key)['username']
				)
			);
		}

	}

	public function output_panel_bottom_right() {

		// If we are connected, output the Save button
		if ( ! $this->is_connected ) {
			$button_label = __( 'Save settings', 'automator-sample' );

			$this->submit_button( $button_label );
		} else {

			// Otherwise, show a button that will redirect with the disconnect flag in the URL
			$button_label = __( 'Disconnect', 'automator-sample' );
			$link = $this->get_settings_page_url() . '&disconnect=1';

			$this->redirect_button( $button_label, $link );
		}
	}

	public function validate_settings($api_url, $api_key) {
        // Retrieve Mastodon API key from WordPress options

        // Make the request to the Mastodon API to get account information
        $response = wp_remote_get($api_url . '/api/v1/accounts/verify_credentials', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        ));
    
        // Check for errors in the response
        if ( is_wp_error( $response ) ) {
            return $response->get_error_code();
        }
    
        // Decode the response
        $response_data = json_decode( wp_remote_retrieve_body( $response ), true );
    
        // Check for Mastodon API errors
        if ( isset( $response_data['error'] ) ) {
            return $response_data['error'];
        }
    
        // Return the account name
        return 0;
    }

	public function get_mastodon_account_name($api_url, $api_key) {

        // Make the request to the Mastodon API to get account information
        $response = wp_remote_get( $api_url . '/api/v1/accounts/verify_credentials', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
        ));
    
        // Check for errors in the response
        if ( is_wp_error( $response ) ) {
            return $response->get_error_code();
        }
    
        // Decode the response
        $response_data = json_decode( wp_remote_retrieve_body( $response ), true );
    
		return $response_data;
    }

	public function settings_updated() {

		// Get the setting
		$this->api_key = get_option( 'mastodon_api_key', false );
		$this->api_url = get_option( 'mastodon_url', false );

		// Run any validation and add alerts

		$error_code = $this->validate_settings($this->api_url, $this->api_key);
		
		if ($error_code !== 0) {
			$this->add_alert(
				array(
					'type'    => 'error',
					'heading' => __( 'Error', 'automator-mastodon' ),
					'content'  => 'Error code: ' . $error_code
				)
			);
			delete_option( 'mastodon_api_key' );
		}
		else
		{
			$this->add_alert(
				array(
					'type'    => 'success',
					'heading' => __( 'You entered all fields', 'automator-mastodon' ),
					'content'  => 'Additional content'
				)
			);
		}

//		} else {
			// Delete the invalid APi key
//			delete_option( 'sample_api_key' );

			// Display an error
//			$this->add_alert(
//				array(
//					'type'    => 'error',
//					'heading' => __( 'Your API key is not a number!', 'automator-sample' ),
//					'content' =>  __( 'The API key failed the numeric check', 'automator-sample' ),
//				)
//			);
//		}
	}

	public function disconnect() {

		// Make sure this settings page is the one that is active
		if ( ! $this->is_current_page_settings() ) {
			return;
		}

		// Check that the URL has our custom disconnect flag
		if ( '1' !== automator_filter_input( 'disconnect' ) ) {
			return;
		}

		// Delete the API key
		delete_option( 'mastodon_api_key' );
		delete_option( 'mastodon_url' );


		// Redirect back to the settings page
		wp_safe_redirect( $this->get_settings_page_url() );

		exit;
	}
}