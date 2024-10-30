<?php

// uninstall file for Auto Post Scheduler

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) 
	exit();

        delete_option('cmh_highlight_keywords');
?>
