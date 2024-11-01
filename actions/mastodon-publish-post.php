<?php

/**
 * Class Mastodon_Publish_Post
 */
class Mastodon_Publish_Post extends \Uncanny_Automator\Recipe\Action {
	
	protected function setup_action() {

		// Define the Actions's info

		$this->set_integration( 'MASTODON' );
		$this->set_action_code( 'SEND_MASTODON_POST' );
		$this->set_action_meta( 'SEND_MASTODON_POST_META' );
		$this->set_requires_user( false );
		$this->set_is_pro( false );

		// Define the Action's sentence
		$this->set_sentence( sprintf(esc_attr__( 'Send an message from Mastodon Integration {{mastodon:%1$s}}', 'automator-mastodon' ), $this->get_action_meta() ) );
		$this->set_readable_sentence( esc_attr__( 'Send an message from {{mastodon}} Mastodon Integration', 'automator-mastodon' ) );

		$this->set_background_processing( true );

		// Disables wpautop.
		$this->set_wpautop( false );	
	}
	
	
	/**
	 * Define the Action's options
	 *
	 * @return void
	 */
	public function options() {

		$helpers = new Mastodon_Helpers();

		return array(

			Automator()->helpers->recipe->field->select(
            	array(

					'option_code'           => $this->get_action_meta(),
					'label'                 => 'Mastodon account',
					'input_type'            => 'select',
					'supports_custom_value' => false,
					'required'              => true,
					'options'               => $helpers->get_mastodon_account_name(),
            	)
			),
            // The photo url field.
			Automator()->helpers->recipe->field->text(
            	array(
                    'option_code' => 'MASTODON_PUBLISH_PHOTO_IMAGE_ID',
                    'label'       => 'Media library ID',
                    'input_type'  => 'url',
                    'description' => 'Enter the URL or the Media library ID of the image you wish to share. The image must be publicly accessible.',
					'required'    => true,
            	)
			),
            // The message field.
			Automator()->helpers->recipe->field->text(
            	array(
                    'option_code' => 'MASTODON_PUBLISH_MESSAGE',
                    'label'       => 'Message',
                    'placeholder' => 'The context of the image or description.', 'uncanny-automator',
                    'input_type'  => 'textarea',
					'required'    => false,
            	)
			),
        );
	}
	
	
	/**
	 * @param int $user_id
	 * @param array $action_data
	 * @param int $recipe_id
	 * @param array $args
	 * @param $parsed
	 */
	protected function process_action($user_id, $action_data, $recipe_id, $args, $parsed) {
		$action_meta = $action_data['meta'];
		$helpers = new Mastodon_Helpers();
	
		// Retrieve and validate Mastodon URL and API key
		$mastodon_url = get_option('mastodon_url', false);
		$api_key = get_option('mastodon_api_key', false);
	
		if (!$mastodon_url || !$api_key) {
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action($user_id, $action_data, $recipe_id, 'Missing Mastodon URL or API key.');
			return;
		}
	
		// Sanitize fields
		$media = sanitize_text_field(Automator()->parse->text($action_meta['MASTODON_PUBLISH_PHOTO_IMAGE_ID'], $recipe_id, $user_id, $args));
		$message = sanitize_textarea_field(Automator()->parse->text($action_meta['MASTODON_PUBLISH_MESSAGE'], $recipe_id, $user_id, $args));
	
		// Truncate message to 500 characters if needed
		if (strlen($message) > 500) {
			$message = substr($message, 0, 496) . '...';
		}
	
		try {
			// Attempt to post media to Mastodon
			$media_id = $helpers->post_media_to_mastodon($media, $mastodon_url, $api_key);
			
			if ($media_id === false) {
				$media_id = 0;
			}
	
			// Post the message with the media
			$post_id = $helpers->post_message_to_mastodon($message, $media_id, $mastodon_url, $api_key);
			if ($post_id === false) {
				$action_data['complete_with_errors'] = true;
				Automator()->complete_action($user_id, $action_data, $recipe_id, 'Failed to post message to Mastodon.');
			} else {
				// Mark action as completed successfully
				Automator()->complete_action($user_id, $action_data, $recipe_id);
			}
		} catch (Exception $e) {
			// Log any unexpected errors and mark the action as incomplete
			$action_data['complete_with_errors'] = true;
			Automator()->complete_action($user_id, $action_data, $recipe_id, 'An error occurred: ' . $e->getMessage());
		}
	}
	
}