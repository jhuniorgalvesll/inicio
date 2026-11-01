<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'fsb_options' );
delete_option( 'fsb_db_version' );
