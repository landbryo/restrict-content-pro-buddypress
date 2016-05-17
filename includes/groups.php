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
		add_filter( 'bp_active_components', array( $this, 'active_components' ) );
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'bp_group_admin_edit_after',  array( $this, 'save_meta_data' ) );
		add_action( 'bp_setup_globals',           array( $this, 'maybe_restrict_group' ), 11 );
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
		$rcp_meta_box = $this->get_metabox_fields();

		// Use nonce for verification
		echo '<input type="hidden" name="rcp_meta_box" value="', wp_create_nonce( basename( __FILE__ ) ), '" />';

		echo '<table class="form-table">';

		echo '<tr><td colspan="3">' . __( 'Use these options to restrict this entire group by subscription level.', 'rcpbp' ) . '</td></tr>';

		do_action( 'rcp_metabox_fields_before' );

		foreach ( $rcp_meta_box['fields'] as $field ) {
			// get current post meta data
			$meta = groups_get_groupmeta( $group->id, $field['id'], true );

			echo '<tr>';
			echo '<th style="width:20%" class="rcp_meta_box_label"><label for="', $field['id'], '">', $field['name'], '</label></th>';
			echo '<td class="rcp_meta_box_field">';
			switch ($field['type']) {
				case 'select':

					echo '<select name="', $field['id'], '" id="', $field['id'], '">';
					foreach ( $field['options'] as $option ) {
						echo '<option', $meta == $option ? ' selected="selected"' : '', '>', $option, '</option>';
					}
					echo '</select>';
					break;
				case 'levels':

					$selected = is_array( $meta ) ? $meta : array( $meta );


					$levels = rcp_get_subscription_levels( 'all' );
					foreach ( $levels as $level ) {
						echo '<input type="checkbox" value="' . $level->id . '"' . checked( true, in_array( $level->id, $selected ), false ) . ' name="' . $field['id'] . '[]" id="' . $field['id'] . '_' . $level->id . '" />&nbsp;';
						echo '<label for="' . $field['id'] . '_' . $level->id . '">' . $level->name . '</label><br/>';
					}
					break;
				case 'checkbox':
					echo '<input type="checkbox" value="1" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
					break;
			}
			echo '</td>';
			echo '<td class="rcp_meta_box_desc">', $field['desc'], '</td>';
			echo '</tr>';
		}

		do_action( 'rcp_metabox_fields_after' );

		echo '<tr><td colspan="3"><strong>' . __( 'Note 1', 'rcp' ) . '</strong>: ' . __( 'To hide this content from logged-out users, but allow free and paid, set the User Level to "Subscriber".', 'rcp' ) . '</td></tr>';
		echo '<tr><td colspan="3"><strong>' . __( 'Note 2', 'rcp' ) . '</strong>: ' . __( 'Access level, subscription level, and user level can all be combined to require the user meet all three specifications.', 'rcp' ) . '</td></tr>';

		echo '</table>';
	}

	protected function get_metabox_fields() {

		//custom meta boxes
		$rcp_prefix = 'rcp_';

		$rcp_meta_box  = array(
			'id'       => 'rcp_meta_box',
			'title'    => __( 'Restrict this content', 'rcp' ),
			'context'  => 'normal',
			'priority' => apply_filters( 'rcp_metabox_priority', 'high' ),
			'fields'   => array(
				array(
					'name' => __( 'Paid Only?', 'rcp' ),
					'id'   => '_is_paid',
					'type' => 'checkbox',
					'desc' => __( 'Restrict this group to active paid users only.', 'rcpbp' )
				),
				array(
					'name' => __( 'Hide Group Landing Page?', 'rcp' ),
					'id'   => $rcp_prefix . 'show_excerpt',
					'type' => 'checkbox',
					'desc' => __( 'Allow non active users to view the group main page if the group is not hidden? If checked, all pages of this group will be totally restricted.', 'rcpbp' )
				),
				array(
					'name' => __( 'Access Level', 'rcp' ),
					'id'   => $rcp_prefix . 'access_level',
					'type' => 'select',
					'desc' => __( 'Choose the access level required to view this group. The access level is determined by the subscription the member is subscribed to.', 'rcpbp' ),
					'options' => rcp_get_access_levels(),
					'std'  => 'All'
				),
				array(
					'name' => __( 'Subscription Level', 'rcp' ),
					'id'   => $rcp_prefix . 'subscription_level',
					'type' => 'levels',
					'desc' => __( 'Choose the subscription levels allowed to view this group.', 'rcpbp' ),
					'std'  => 'All'
				),
				array(
					'name' => __( 'User Level', 'rcp' ),
					'id'   => $rcp_prefix . 'user_level',
					'type' => 'select',
					'desc' => __( 'Choose the user level that can view this group. Users of this level and higher will be the only ones able to view this group.', 'rcpbp' ),
					'options' => array('All', 'Administrator', 'Editor', 'Author', 'Contributor', 'Subscriber'),
					'std'  => 'All'
				)
			)
		);

		return apply_filters( 'rcpbp_group_metabox_fields', $rcp_meta_box );
	}


	function save_meta_data( $group_id ) {

		$rcp_meta_box = $this->get_metabox_fields();

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

		foreach ( $rcp_meta_box['fields'] as $field ) {
			if( isset( $_POST[$field['id']] ) ) {

				$old = groups_get_groupmeta( $group_id, $field['id'], true );
				$data = $_POST[$field['id']];

				if ( ( $data || $data == 0 ) && $data != $old ) {
					groups_update_groupmeta( $group_id, $field['id'], $data) ;
				} elseif ( '' == $data && $old ) {
					groups_delete_groupmeta( $group_id, $field['id'], $old );
				}
			} else {
				groups_delete_groupmeta( $group_id, $field['id'] );
			}
		}
	}

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

		// Assume the user can until proven false
		$ret = true;

		if ( $this->is_paid_group( $group->id ) && ! $member->is_active() ) {

			$ret = false;

		}

		if( ! rcp_user_has_access( $member->ID, $access_level ) && $access_level > 0 ) {

			$ret = false;

		}

		if ( ! empty( $subscription_levels ) ) {

			if ( ! in_array( $member->get_subscription_id(), $subscription_levels ) && ! user_can( $member->ID, 'manage_options' ) ) {

				$ret = false;

			}

		}

		return apply_filters( 'rcpbp_member_can_access', $ret, $member->ID, $group, $this );

	}

	public function get_subscription_levels( $group_id = 0 ) {
		$levels = groups_get_groupmeta( $group_id, 'rcp_subscription_level', true );

		if( 'all' == $levels ) {
			// This is for backwards compatibility from when RCP didn't allow content to be restricted to multiple levels
			return false;
		}

		if( ! empty( $levels ) && ! is_array( $levels ) ) {
			$levels = array( $levels );
		}

		return apply_filters( 'rcpbp_get_subscription_levels', $levels, $group_id );
	}

	public function is_paid_group( $group_id ) {
		$return = false;

		$is_paid = groups_get_groupmeta( $group_id, '_is_paid', true );
		if ( $is_paid ) {
			// this post is for paid users only
			$return = true;
		}

		return (bool) apply_filters( 'rcpbp_is_paid_group', $return, $group_id );
	}

}