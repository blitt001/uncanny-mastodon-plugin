<?php 

class Mastodon_Helpers {

    public function get_mastodon_account_name() {
		$accounts = array();

		$accounts[] = array(
			'value' => '1',
			'text' => 'Mastodon Account',
		);

		return $accounts;
	}

    public function removeQueryParameters($url) {
        // Parse the URL to separate its components
        $parsedUrl = parse_url($url);
    
        // Rebuild the URL without the query parameters
        $cleanUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
        
        // Add the port if specified (e.g., for non-standard ports)
        if (isset($parsedUrl['port'])) {
            $cleanUrl .= ':' . $parsedUrl['port'];
        }
        
        // Add the path if specified
        if (isset($parsedUrl['path'])) {
            $cleanUrl .= $parsedUrl['path'];
        }
    
        return $cleanUrl;
    }

    public function post_media_to_mastodon($image_path, $mastodon_url, $api_key) {

        $image_data = wp_remote_get($this->removeQueryParameters($image_path), [
            'blocking' => true
        ]);

        if (is_wp_error($image_data)) {
            error_log( 'Image download error: ' . $image_data->get_error_message());
        }
        // Prepare the file for upload
        
        $image_body = wp_remote_retrieve_body($image_data);
        $mime_type = wp_remote_retrieve_header($image_data, 'content-type');
        
        if (!$mime_type) {
            $mime_type = 'image/jpeg'; // default fallback
        }
                
        $filename = basename(parse_url($image_path, PHP_URL_PATH));

        $base64_image_body = base64_encode($image_body);

        // Prepare headers for Mastodon API
        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ];

        // Prepare body
        $body = json_encode([
            'file' => 'data:' . $mime_type . ';base64,' . $base64_image_body
        ]);

        // Send the base64-encoded image upload request to Mastodon
        $upload_response = wp_remote_post($mastodon_url . '/api/v2/media', [
            'headers' => $headers,
            'body' => $body
        ]);

        if (is_wp_error($upload_response)) {
            error_log('Mastodon upload error: ' . $upload_response->get_error_message());
        }

        // Parse the response to get the media ID
        $upload_body = json_decode(wp_remote_retrieve_body($upload_response));

        $media_id = isset( $upload_body->id ) ? $upload_body->id : 0;

        if ($media_id === 0) {
            error_log('No media ID received from Mastodon');
        }

        return $media_id;
    }

    public function post_message_to_mastodon($message, $media_id, $mastodon_url, $api_key) {
        
        // Prepare headers for Mastodon API
        $data = array(
			'status' => $message,
		);

        if ($media_id > 0) {
            $data['media_ids[]'] = $media_id;
        }
    
        // Post the status with the media
        $response = wp_remote_post( $mastodon_url . '/api/v1/statuses', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body'    => $data,
        ));
    
        $response_data = json_decode( wp_remote_retrieve_body( $response ), true );

        $post_id = isset( $response_data->id ) ? $response_data->id : 0;
        
        return $post_id;

    }
    
}
