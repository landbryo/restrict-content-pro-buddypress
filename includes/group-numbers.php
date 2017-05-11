<?php

RCPBP_Group_Numbers::get_instance();
class RCPBP_Group_Numbers {

	/**
	 * @var
	 */
	protected static $_instance;

	/**
	 * Only make one instance of the RCPBP_Member_Types
	 *
	 * @return RCPBP_Member_Types
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof RCPBP_Group_Numbers ) {
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

	/**
	 * Actions and Filters
	 */
	protected function hooks() {
		add_action( 'rcp_add_subscription_form',   array( $this, 'subscription_number_groups' ), 10, 1 );
		add_action( 'rcp_edit_subscription_form',  array( $this, 'subscription_number_groups' ), 10, 1 );
		add_action( 'rcp_edit_subscription_level', array( $this, 'subscription_number_groups_save' ), 10, 2 );
		add_action( 'rcp_add_subscription',        array( $this, 'subscription_number_groups_save' ), 10, 2 );

	}

	public function subscription_number_groups ($level = null) {

		global $rcp_levels_db;

		if ( ! $types = bp_get_member_types( array(), 'all' ) ) {
			return;
		}

		$sub_level_limit = $rcp_levels_db->get_meta( $level->id,'rcpbp_group_limit', $single = true );

		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label  for="rcp-role"><?php _e( 'Group limit', 'rcpbp' ); ?></label>
			</th>
			<td>
				<input style="max-width: 106px" name="rcpbp-group-limit" type="number" id="rcp-group-numbers" value="<?php echo $sub_level_limit ?>">
				<p class="description"><?php _e( 'Number of groups allowed to create (-1 for unlimited)', 'rcpbp' ); ?></p>
			</td>
		</tr>
		<?php
	}

	public function subscription_number_groups_save ($subscription_id, $args) {

		//get the global
		global $rcp_levels_db;

		// remove the old data
		$rcp_levels_db->delete_meta( $subscription_id, 'rcpbp_group_limit' );

		// update with the new data
		$rcp_levels_db->add_meta( $subscription_id, 'rcpbp_group_limit', $args['rcpbp-group-limit'],true );

	}
}