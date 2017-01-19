<?php

RCPBP_Groups::get_instance();
class RCPBP_Groups {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the RCPBP_Groups
	 *
	 * @return RCPBP_Groups
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof RCPBP_Groups ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		$this->hooks();
	}

	protected function hooks() {
//		add_filter( 'bp_active_components', array( $this, 'active_components' ) );

		add_action( 'bp_groups_admin_meta_boxes',       array( $this, 'add_meta_box' ) );
		add_action( 'bp_group_admin_edit_after',        array( $this, 'save_meta_data' ) );
		add_action( 'bp_setup_globals',                 array( $this, 'maybe_restrict_group' ), 11 );
		add_action( 'rcpbp_metabox_group_fields_after', array( $this, 'bp_fields' ) );
	}

	public function active_components( $active ) {
//		unset( $active['groups'] );
		return $active;
	}

	public function add_meta_box() {
		$rcp_meta_box = rcp_get_metabox_fields();
		add_meta_box( $rcp_meta_box['id'], __( 'Membership restrictions for this group.', 'rcpbp' ), array( $this, 'render_meta_box' ), get_current_screen()->id, $rcp_meta_box['context'], $rcp_meta_box['priority'] );
	}

	public function render_meta_box( $group ) {
		wp_enqueue_style( 'rcpbp-admin-css' );
		wp_enqueue_script( 'rcpbp-admin-js' );

		// enqueue RCP assets
		wp_enqueue_script( 'bbq',  RCP_PLUGIN_URL . 'includes/js/jquery.ba-bbq.min.js' );
		wp_enqueue_script( 'rcp-admin-scripts',  RCP_PLUGIN_URL . 'includes/js/admin-scripts.js', array( 'jquery', 'jquery-ui-tooltip', 'jquery-ui-sortable', 'jquery-ui-datepicker' ), RCP_PLUGIN_VERSION );
		wp_enqueue_style( 'rcp-admin',  RCP_PLUGIN_URL . 'includes/css/admin-styles.css', array(), RCP_PLUGIN_VERSION );

		// Use nonce for verification
		echo '<input type="hidden" name="rcp_meta_box" value="' . wp_create_nonce( basename( __FILE__ ) ) . '" />';

		do_action( 'rcpbp_metabox_group_fields_before' );

		include RCPBP_PATH . 'includes/admin/metabox-view-group.php';

		do_action( 'rcpbp_metabox_group_fields_after' );
	}

	/**
	 * Handle extra restriction fields for member types and groups
	 */
	public function bp_fields() {

		if ( empty( $_GET['gid'] ) ) {
			return;
		}

		$group_id = absint( $_GET['gid'] );

		$member_types = apply_filters( 'rcpbp_restricted_member_types', bp_get_member_types( array(), 'objects' ) );

		wp_nonce_field( basename( __FILE__ ), 'rcpbp_meta_box' );

		if ( ! empty( $member_types ) ) {
			$this->member_type_field( $member_types, $group_id );
		}

		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		$groups = apply_filters( 'rcpbp_restricted_groups', groups_get_groups( 'show_hidden=true' ) );

		if ( ! empty( $groups['groups'] ) ) {
			$this->groups_field( $groups['groups'], $group_id );
		}

	}

	/**
	 * Print out member type fields for restricted content section
	 *
	 * @param $member_types
	 */
	protected function member_type_field( $member_types, $group_id ) {
		$field_id = 'rcpbp_member_types';
		$selected = groups_get_groupmeta( $group_id, $field_id, true );
		?>
		<div id="rcp-metabox-field-member-types" class="rcp-metabox-field">
			<p><?php _e( 'Require member to have any one of the selected member types.', 'rcpbp' ); ?></p>
			<?php foreach ( $member_types as $name => $member_type ) : ?>
				<input type="checkbox" value="<?php echo esc_attr( $name ); ?>" <?php checked( true, in_array( $name, (array) $selected ) ); ?> name="<?php echo $field_id; ?>[]" id="<?php echo $field_id; ?>_<?php echo esc_attr( $name ); ?>" />&nbsp;
				<label for="<?php echo $field_id; ?>_<?php echo esc_attr( $name ); ?>"><?php echo esc_html( $member_type->labels['name'] ); ?></label><br/>
			<?php endforeach; ?>
		</div>
		<?php
	}

	/**
	 * Print out group fields for the restricted content section
	 *
	 * @param $groups
	 */
	protected function groups_field( $groups, $group_id ) {
		$field_id = 'rcpbp_groups';
		$selected = groups_get_groupmeta( $group_id, $field_id, true );
		?>
		<div id="rcp-metabox-field-groups" class="rcp-metabox-field">
			<p><?php _e( 'Require member to be a part of any one of the selected groups.', 'rcpbp' ); ?></p>
			<?php foreach ( $groups as $group ) : ?>
				<input type="checkbox" value="<?php echo absint( $group->id ); ?>" <?php checked( true, in_array( $group->id, (array) $selected ) ); ?> name="<?php echo $field_id; ?>[]" id="<?php echo $field_id; ?>_<?php echo absint( $group->id ); ?>" />&nbsp;
				<label for="<?php echo $field_id; ?>_<?php echo absint( $group->id ); ?>"><?php echo esc_html( $group->name ); ?></label><br/>
			<?php endforeach; ?>
		</div>
	<?php
	}


	/**
	 * Save meta value
	 *
	 * @param $group_id
	 */
	function save_meta_data( $group_id ) {

		// verify nonce
		if ( !isset( $_POST['rcp_meta_box'] ) || !wp_verify_nonce( $_POST['rcp_meta_box'], basename( __FILE__ ) ) ) {
			return;
		}

		// check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'bp_moderate' ) ) {
			return;
		}

		$is_paid     = false;
		$restrict_by = sanitize_text_field( $_POST['rcp_restrict_by'] );

		switch ( $restrict_by ) {

			case 'unrestricted' :

				groups_delete_groupmeta( $group_id, 'rcp_access_level' );
				groups_delete_groupmeta( $group_id, 'rcp_subscription_level' );
				groups_delete_groupmeta( $group_id, 'rcp_user_level' );

				break;


			case 'subscription-level' :

				$level_set = sanitize_text_field( $_POST['rcp_subscription_level_any_set'] );

				switch ( $level_set ) {

					case 'any' :

						groups_update_groupmeta( $group_id, 'rcp_subscription_level', 'any' );

						break;

					case 'any-paid' :

						$is_paid = true;
						groups_update_groupmeta( $group_id, 'rcp_subscription_level', 'any-paid' );

						break;

					case 'specific' :

						$levels = array_map( 'absint', $_POST['rcp_subscription_level'] );

						foreach ( $levels as $level ) {

							$price = rcp_get_subscription_price( $level );
							if ( ! empty( $price ) ) {
								$is_paid = true;
								break;
							}

						}

						groups_update_groupmeta( $group_id, 'rcp_subscription_level', $levels );

						break;

				}

				// Remove unneeded fields
				groups_delete_groupmeta( $group_id, 'rcp_access_level' );

				break;


			case 'access-level' :

				groups_update_groupmeta( $group_id, 'rcp_access_level', absint( $_POST['rcp_access_level'] ) );

				$levels = rcp_get_subscription_levels();
				foreach ( $levels as $level ) {

					if ( ! empty( $level->price ) ) {
						$is_paid = true;
						break;
					}

				}

				// Remove unneeded fields
				groups_delete_groupmeta( $group_id, 'rcp_subscription_level' );

				break;

			case 'registered-users' :

				// Remove unneeded fields
				groups_delete_groupmeta( $group_id, 'rcp_access_level' );

				// Remove unneeded fields
				groups_delete_groupmeta( $group_id, 'rcp_subscription_level' );

				$levels = rcp_get_subscription_levels();
				foreach ( $levels as $level ) {

					if ( ! empty( $level->price ) ) {
						$is_paid = true;
						break;
					}

				}

				break;

		}
		
		// buddypress settings
		if ( 'unrestricted' == $restrict_by ) {
			groups_delete_groupmeta( $group_id, 'rcpbp_member_types' );
			groups_delete_groupmeta( $group_id, 'rcpbp_groups' );
		} else {
			if ( empty( $_POST['rcpbp_groups'] ) ) {
				groups_delete_groupmeta( $group_id, 'rcpbp_groups' );
			} else {
				groups_update_groupmeta( $group_id, 'rcpbp_groups', array_map( 'absint', $_POST['rcpbp_groups'] ) );
			}

			if ( empty( $_POST['rcpbp_member_types'] ) ) {
				groups_delete_groupmeta( $group_id, 'rcpbp_member_types' );
			} else {
				groups_update_groupmeta( $group_id, 'rcpbp_member_types', array_map( 'sanitize_text_field', $_POST['rcpbp_member_types'] ) );
			}
		}


		$show_excerpt = isset( $_POST['rcp_show_excerpt'] );
		$hide_in_feed = isset( $_POST['rcp_hide_from_feed'] );
		$user_role    = sanitize_text_field( $_POST['rcp_user_level'] );

		groups_update_groupmeta( $group_id, 'rcp_show_excerpt', $show_excerpt );
		groups_update_groupmeta( $group_id, 'rcp_hide_from_feed', $hide_in_feed );
		if ( 'unrestricted' !== $_POST['rcp_restrict_by'] ) {
			groups_update_groupmeta( $group_id, 'rcp_user_level', $user_role );
		}

		groups_update_groupmeta( $group_id, '_is_paid', $is_paid );

		do_action( 'rcpbp_group_meta_data_saved', $group_id );
	}

	/**
	 * Maybe restrict this group
	 */
	public function maybe_restrict_group() {
		$bp = buddypress();

		if ( ! bp_is_group() ) {
			return;
		}

		if ( ! $group = groups_get_current_group() ) {
			return;
		}

		if ( $group->is_user_member ) {
			return;
		}

		if ( $this->user_can_access( $group ) ) {
			return;
		}

		$bp->groups->current_group->status = 'restricted';
		$bp->groups->current_group->is_visible = $bp->groups->current_group->user_has_access = false;

	}

	public function user_can_access( $group = null, $user_id = null ) {

		if ( empty( $group ) ) {
			$group = groups_get_current_group();
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$member = new RCP_Member( $user_id );

		$subscription_levels = $this->get_subscription_levels( $group->id );
		$access_level        = groups_get_groupmeta( $group->id, 'rcp_access_level', true );
		$user_level          = groups_get_groupmeta( $group->id, 'rcp_user_level', true );
		$sub_id              = $member->get_subscription_id();

		// Assume the user can until proven false
		$ret = true;

		if ( $this->is_paid_group( $group->id ) && $member->is_expired() ) {

			$ret = false;

		}

		if ( ! empty( $subscription_levels ) ) {

			if( is_string( $subscription_levels ) ) {

				switch( $subscription_levels ) {

					case 'any' :

						$ret = ! empty( $sub_id ) && ! $member->is_expired();
						break;

					case 'any-paid' :

						$ret = $member->is_active();
						break;
				}

			} else {

				if ( in_array( $sub_id, $subscription_levels ) ) {

					$needs_paid = false;

					foreach( $subscription_levels as $level ) {
						$price = rcp_get_subscription_price( $level );
						if ( ! empty( $price ) && $price > 0 ) {
							$needs_paid = true;
						}
					}

					if ( $needs_paid ) {

						$ret = $member->is_active();

					} else {

						$ret = true;
					}

				} else {

					$ret = false;

				}
			}
		}

		if ( ! rcp_user_has_access( $member->ID, $access_level ) && $access_level > 0 ) {

			$ret = false;

		}

		if ( $ret && ! empty( $user_level ) && 'All' != $user_level ) {
			if ( ! user_can( $member->ID, strtolower( $user_level ) ) ) {
				$ret = false;
			}
		}

		if( user_can( $member->ID, 'manage_options' ) ) {
			$ret = true;
		}

		return apply_filters( 'rcpbp_member_can_access', $ret, $member->ID, $group, $this );

	}

	/**
	 * Get group subscription levels
	 *
	 * @param int $group_id
	 *
	 * @return bool|mixed|void
	 */
	public function get_subscription_levels( $group_id = 0 ) {
		$levels = groups_get_groupmeta( $group_id, 'rcp_subscription_level', true );

		if( 'all' == $levels ) {
			// This is for backwards compatibility from when RCP didn't allow content to be restricted to multiple levels
			return false;
		}

		if( 'any' !== $levels && 'any-paid' !== $levels && ! empty( $levels ) && ! is_array( $levels ) ) {
			$levels = array( $levels );
		}

		return apply_filters( 'rcpbp_get_subscription_levels', $levels, $group_id );
	}

	/**
	 * Determine if this group is a paid group
	 *
	 * @param $group_id
	 *
	 * @return bool
	 */
	public function is_paid_group( $group_id ) {
		$return = false;

		$is_paid = groups_get_groupmeta( $group_id, '_is_paid', true );
		if ( $is_paid ) {
			$return = true;
		}

		return (bool) apply_filters( 'rcpbp_is_paid_group', $return, $group_id );
	}

}