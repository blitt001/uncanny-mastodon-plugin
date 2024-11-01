<?php

/*
Plugin Name: Uncanny Automator - Custom Mastodon Integration
Plugin URI: https://github.com/blitt001/uncanny-mastodon-plugin
Description: A custom plugin to add Mastodon support for Uncanny Automator.
Version: 1.1
Author: Jeroen van Blitterswijk
License: GPL2
*/

add_action( 'automator_add_integration', 'mastodon_integration_load_files' );

function mastodon_integration_load_files() {

	// If this class doesn't exist Uncanny Automator plugin is not enabled or needs to be updated.
	if ( ! class_exists( '\Uncanny_Automator\Integration' ) ) {
		return;
	}
	require_once 'helpers/mastodon-helpers.php';
	
	require_once 'mastodon-integration.php';
	new Mastodon_Integration;

	require_once 'settings/settings-mastodon.php';
	new Mastodon_Integration_Settings;

	require_once 'actions/mastodon-publish-post.php';
	new Mastodon_Publish_Post;
}

