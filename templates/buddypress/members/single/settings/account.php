<?php

/**
 * Fires before the display of member settings template.
 */
do_action( 'bp_before_member_settings_template' );

rcp_get_template_part( 'subscription' );
echo do_shortcode( 'rcp_update_card' );

/**
 * Fires after the display of member settings template.
 */
do_action( 'bp_after_member_settings_template' );
