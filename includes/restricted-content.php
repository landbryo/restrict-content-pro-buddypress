<?php

RCPBP_Restricted_Content::get_instance();
class RCPBP_Restricted_Content {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Whether or not the content failed the member type restriction test
	 *
	 * @var bool
	 */
	public static $_is_restricted_member_type = false;

	/**
	 * Whether or not the content failed the group restriction test
	 *
	 * @var bool
	 */
	public static $_is_restricted_group = false;

	/**
	 * Only make one instance of the RCPBP_Restricted_Content
	 *
	 * @return RCPBP_Restricted_Content
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof RCPBP_Restricted_Content ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add Hooks and Actions
	 */
	protected function __construct() {
		add_action( 'rcp_metabox_fields_after', array( $this, 'bp_fields' ) );
		add_action( 'save_post', array( $this, 'save_meta_data' ) );
		add_filter( 'rcp_member_can_access', array( $this, 'can_access' ), 10, 4 );
		add_filter( 'rcp_restricted_message', array( $this, 'restricted_message_filter' ), 100 );
	}

	/**
	 * Handle extra restriction fields for member types and groups
	 */
	public function bp_fields() {
		global $post;

		$member_types = apply_filters( 'rcpbp_restricted_member_types', bp_get_member_types( array(), 'objects' ) );

		wp_nonce_field( basename( __FILE__ ), 'rcpbp_meta_box' );

		if ( ! empty( $member_types ) ) {
			$this->member_type_field( $member_types, $post->ID );
		}

		if ( ! bp_is_active( 'groups' ) ) {
			return;
		}

		$groups = apply_filters( 'rcpbp_restricted_groups', groups_get_groups( 'show_hidden=true&per_page=999' ) );

		if ( ! empty( $groups['groups'] ) ) {
			$this->groups_field( $groups['groups'], $post->ID );
		}

	}

	/**
	 * Print out member type fields for restricted content section
	 *
	 * @param $member_types
	 */
	protected function member_type_field( $member_types, $post_id ) {
		$field_id = 'rcpbp_member_types';
		$selected = get_post_meta( $post_id, $field_id, true );
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
	protected function groups_field( $groups, $post_id ) {
		$field_id = 'rcpbp_groups';
		$selected = get_post_meta( $post_id, $field_id, true );
		?>
		<div id="rcp-metabox-field-groups" class="rcp-metabox-field">
			<p><?php _e( 'Require member to be a part of any one of the selected groups.', 'rcpbp' ); ?></p>
			<div class="rcpbp-restricted-groups-container">
				<?php foreach ( $groups as $group ) : ?>
					<input type="checkbox" value="<?php echo absint( $group->id ); ?>" <?php checked( true, in_array( $group->id, (array) $selected ) ); ?> name="<?php echo $field_id; ?>[]" id="<?php echo $field_id; ?>_<?php echo absint( $group->id ); ?>" />&nbsp;
					<label for="<?php echo $field_id; ?>_<?php echo absint( $group->id ); ?>"><?php echo esc_html( $group->name ); ?></label><br/>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Save meta value
	 *
	 * @param $post_id
	 */
	public function save_meta_data( $post_id ) {
		// verify nonce
		if ( ! isset( $_POST['rcpbp_meta_box'] ) || ! wp_verify_nonce( $_POST['rcpbp_meta_box'], basename( __FILE__ ) ) ) {
			return;
		}

		// check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// check permissions
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) ) {
				return;
			}

		} elseif ( ! current_user_can( 'edit_post', $post_id ) ) {

			return;

		}

		$restrict_by = sanitize_text_field( $_POST['rcp_restrict_by'] );

		if ( 'unrestricted' == $restrict_by ) {
			delete_post_meta( $post_id, 'rcpbp_member_types' );
			delete_post_meta( $post_id, 'rcpbp_groups' );
		} else {
			if ( empty( $_POST['rcpbp_groups'] ) ) {
				delete_post_meta( $post_id, 'rcpbp_groups' );
			} else {
				update_post_meta( $post_id, 'rcpbp_groups', array_map( 'absint', $_POST['rcpbp_groups'] ) );
			}

			if ( empty( $_POST['rcpbp_member_types'] ) ) {
				delete_post_meta( $post_id, 'rcpbp_member_types' );
			} else {
				update_post_meta( $post_id, 'rcpbp_member_types', array_map( 'sanitize_text_field', $_POST['rcpbp_member_types'] ) );
			}
		}

	}

	/**
	 * Customize user access depending on member type and group membership
	 *
	 * @param $ret
	 * @param $user_id
	 * @param $post_id
	 * @param RCP_Member $rcp_member
	 *
	 * @return bool
	 */
	public function can_access( $ret, $user_id, $post_id, $rcp_member ) {

		// don't check if the user does not already have access
		if ( ! $ret ) {
			return $ret;
		}

		$member_types      = get_post_meta( $post_id, 'rcpbp_member_types', true );
		$groups            = get_post_meta( $post_id, 'rcpbp_groups', true );
		$user_member_types = bp_get_member_type( $user_id, false );
		$user_groups       = array();

		if ( function_exists( 'bp_get_user_groups' ) ) {
			$user_groups = bp_get_user_groups( $user_id, array(
				'is_admin' => null,
				'is_mod'   => null,
			) );
		}

		// Check if the user has one of the given member types
		if ( $member_types ) {
			$has_type = array_intersect( (array) $user_member_types, (array) $member_types );

			if ( ! apply_filters( 'rcpbp_member_can_access_member_types', $has_type, $user_id, $post_id, $ret ) ) {
				$ret = false;
				self::$_is_restricted_member_type = true;
			}
		}

		// Check if the user is a member of any of the given groups
		if ( $groups ) {
			$group_member = array_intersect( array_keys( $user_groups ), $groups );

			if ( ! apply_filters( 'rcpbp_member_can_access_groups', $group_member, $user_id, $post_id, $ret ) ) {
				$ret = false;
				self::$_is_restricted_group = true;
			}
		}

		$has_post_restrictions    = rcp_has_post_restrictions( $post_id );
		$term_restricted_post_ids = rcp_get_post_ids_assigned_to_restricted_terms();

		/**
		 * since no post-level restrictions, check to see if user is restricted via term
		 * @see RCP_Member::can_access()
		 */
		if ( $ret && ! $has_post_restrictions && in_array( $post_id, $term_restricted_post_ids ) ) {

			$restricted = false;

			$terms = (array) rcp_get_connected_term_ids( $post_id );

			if ( ! empty( $terms ) ) {

				foreach( $terms as $term_id ) {

					$restrictions = rcp_get_term_restrictions( $term_id );

					if ( ! empty( $restrictions['member_types'] ) && ! array_intersect( (array) $user_member_types, (array) $restrictions['member_types'] ) ) {
						$restricted = self::$_is_restricted_member_type = true;
						break;
					}

					if ( ! empty( $restrictions['groups'] ) ) {
						if ( ! array_intersect( array_keys( $user_groups ), (array) $restrictions['groups'] ) ) {
							$restricted = self::$_is_restricted_group = true;
							break;
						}
					}

				}
			}

			if ( $restricted ) {
				$ret = false;
			}

		}

		/**
		 * Filter for BuddyPress component restrictions
		 *
		 * @since 1.1.2
		 *
		 * @param bool $ret Whether or not the user can access
		 * @param int $user_id the id of the user
		 * @param int $post_id the id of the post being checked
		 * @param RCP_Member $rcp_member the RCP_Member instance being used
		 *
		 * @author Tanner Moushey
		 */
		return apply_filters( 'rcpbp_member_can_access', $ret, $user_id, $post_id, $rcp_member );
	}

	/**
	 * Custom filter to modify the restricted message when a content is restricted by group or member type
	 *
	 * @param $message
	 *
	 * @since  1.1.3
	 *
	 * @return string $message
	 * @author Tanner Moushey
	 */
	public function restricted_message_filter( $message ) {

		// check to see if the content is restricted by group or member type
		if ( ! ( self::$_is_restricted_group || self::$_is_restricted_member_type ) ) {
			return $message;
		}

		/**
		 * Custom filter for content that is restricted by group or member type
		 *
		 * @since 1.1.3
		 *
		 * @param string $message
		 * @param bool self::$_is_restricted_group shows if this content has an unmet group restriction
		 * @param bool self::$_is_restricted_member_type shows if this content has an unmet member type restriction
		 */
		return apply_filters( 'rcpbp_restricted_message', $message, self::$_is_restricted_group, self::$_is_restricted_member_type );
	}

}