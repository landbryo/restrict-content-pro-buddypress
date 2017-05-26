<?php
RCPBP_Friend_option::get_instance();
class RCPBP_Friend_option {
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
		if ( ! self::$_instance instanceof RCPBP_Friend_option ) {
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
		add_action( 'rcp_add_subscription_form',   array( $this, 'subscription_friend_option' ), 10, 1 );
		add_action( 'rcp_edit_subscription_form',  array( $this, 'subscription_friend_option' ), 10, 1 );
		add_action( 'rcp_edit_subscription_level', array( $this, 'subscription_friend_option_save' ), 10, 2 );
		add_action( 'rcp_add_subscription',        array( $this, 'subscription_friend_option_save' ), 10, 2 );
	}
	public function subscription_friend_option ($level = null) {
		global $rcp_levels_db;
		if ( ! $types = bp_get_member_types( array(), 'all' ) ) {
			return;
		}
		$friend_option = $rcp_levels_db->get_meta( $level->id,'rcpbp_friend_option' );

		$friend_option = $friend_option[0];

		if (! empty($level)) {
			$test = '';
		}


		if (! empty($friend_option)) {
			$test = '';
		}

		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label  for="rcp-role"><?php _e( 'Allow Friends?', 'rcpbp' ); ?></label>
			</th>
			<td>
				<select style="max-width: 106px" name="rcpbp-friend-option" type="number" id="rcpbp-friend-option">
					<?php if ($friend_option === 'Deny') {

						echo '<option>Allow</option><option selected="selected">Deny</option>';
                    } else {
						echo '<option selected="selected">Allow</option><option>Deny</option>';
                    }  ?>

					<option>Allow</option>>

				</select>
				<p class="description"><?php _e( 'Number of groups allowed to create (-1 for unlimited)', 'rcpbp' ); ?></p>
			</td>
		</tr>
		<?php
	}
	public function subscription_friend_option_save ($subscription_id, $args) {
		//get the global
		global $rcp_levels_db;
		// remove the old data
		$rcp_levels_db->delete_meta( $subscription_id, 'rcpbp_friend_option' );
		// update with the new data
		$rcp_levels_db->add_meta( $subscription_id, 'rcpbp_friend_option', $args['rcpbp-friend-option'],true );
	}
}