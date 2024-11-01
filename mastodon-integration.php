<?php

/**
 * Class Mastodon_Integration
 */
class Mastodon_Integration extends \Uncanny_Automator\Integration {
	
	protected function setup() {
		$this->set_integration( 'MASTODON' );
		$this->set_name( 'Mastodon' );
		$this->set_icon_url( plugin_dir_url( __FILE__ ) . 'img/mastodon-icon.svg' );
	}
}