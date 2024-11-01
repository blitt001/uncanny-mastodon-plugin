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
        // Retrieve image data

        $image_path = $this->resolve_image_url($image_path);

        $image_data = wp_remote_get($this->removeQueryParameters($image_path), [
            'blocking' => true
        ]);
    
        // Check if the request returned a WP error
        if (is_wp_error($image_data)) {
            error_log('Image download error: ' . $image_data->get_error_message());
            return false;
        }
    
        // Check for HTTP status code errors in the image download
        $response_code = wp_remote_retrieve_response_code($image_data);
        if ($response_code < 200 || $response_code >= 300) {
            error_log('Image download failed with HTTP status code: ' . $response_code);
            return false;
        }
    
        // Prepare the image data for base64 encoding
        $image_body = wp_remote_retrieve_body($image_data);
        $mime_type = wp_remote_retrieve_header($image_data, 'content-type') ?: 'image/jpeg'; // fallback MIME type
        $base64_image_body = base64_encode($image_body);
    
        // Prepare headers for Mastodon API
        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        ];
    
        // Create JSON body with base64-encoded image data
        $body = json_encode([
            'file' => 'data:' . $mime_type . ';base64,' . $base64_image_body
        ]);
    
        // Send the base64-encoded image upload request to Mastodon
        $upload_response = wp_remote_post($mastodon_url . '/api/v2/media', [
            'headers' => $headers,
            'body'    => $body
        ]);
    
        // Check if the request returned a WP error
        if (is_wp_error($upload_response)) {
            error_log('Mastodon upload error: ' . $upload_response->get_error_message());
            return false;
        }
    
        // Check for HTTP status code errors in the upload response
        $upload_response_code = wp_remote_retrieve_response_code($upload_response);
        if ($upload_response_code < 200 || $upload_response_code >= 300) {
            error_log('Mastodon upload failed with HTTP status code: ' . $upload_response_code);
            return false;
        }
    
        // Decode the response body
        $upload_body = json_decode(wp_remote_retrieve_body($upload_response), true);
    
        // Check for JSON decode errors
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('Mastodon upload error: Unable to decode JSON response - ' . json_last_error_msg());
            return false;
        }
    
        // Check if the response contains an ID for the media
        $media_id = isset($upload_body['id']) ? $upload_body['id'] : 0;
    
        if ($media_id === 0) {
            error_log('Mastodon upload error: No media ID returned in response.');
            return false;
        }
    
        // Return the media ID if everything is successful
        return $media_id;
    }
        
    
    public function post_message_to_mastodon($message, $media_id, $mastodon_url, $api_key) {
        // Prepare the data for posting the status
        $data = [
            'status' => $message
        ];
    
        if ($media_id > 0) {
            $data['media_ids'] = [$media_id]; // Set media_ids as an array
        }
    
        // Prepare headers for Mastodon API
        $headers = [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json'
        ];
    
        // Post the status with the media
        $response = wp_remote_post($mastodon_url . '/api/v1/statuses', [
            'headers' => $headers,
            'body'    => json_encode($data)
        ]);
    
        // Check if the request returned a WP error
        if (is_wp_error($response)) {
            error_log('Mastodon post error: ' . $response->get_error_message());
            return false;
        }
    
        // Check for HTTP status code errors
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code < 200 || $response_code >= 300) {
            error_log('Mastodon post failed with HTTP status code: ' . $response_code);
            return false;
        }
    
        // Decode the response body
        $response_data = json_decode(wp_remote_retrieve_body($response), true);
    
        // Check if the response contains an ID for the post
        if (!isset($response_data['id'])) {
            error_log('Mastodon post error: No post ID returned in response.');
            return false;
        }
    
        // Return the post ID if everything is successful
        return $response_data['id'];
    }

	/**
	 * Resolves the image URL.
	 *
	 * @param mixed $image The public image URL or the Media Library ID.
	 *
	 * @return string The URL of the image or false if its failing.
	 */
	private function resolve_image_url( $media = '' ) {
		return is_numeric( $media ) ? wp_get_attachment_url( $media ) : $media;
	}

        
}
