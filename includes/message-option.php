<?php
RCPBP_Message_options::get_instance();
class RCPBP_Message_options {
	/**
	 * @var
	 */
	protected static $_instance;
	/**
	 * Only make one instance of the RCPBP_Message_options
	 *
	 * @return RCPBP_Message_options
	 */
	public static function get_instance() {
		if ( ! self::$_instance instanceof RCPBP_Message_options ) {
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
		add_action( 'rcp_add_subscription_form',   array( $this, 'subscription_message_option' ));
		add_action( 'rcp_edit_subscription_form',  array( $this, 'subscription_message_option' ));
		add_action( 'rcp_edit_subscription_level', array( $this, 'subscription_message_option_save' ), 10, 2 );
		add_action( 'rcp_add_subscription',        array( $this, 'subscription_message_option_save' ), 10, 2 );


	}
	public function subscription_message_option ($level= null) {

		global $rcp_levels_db;

		if ( ! $types = bp_get_member_types( array(), 'all' ) ) {
			return;
		}
		$message_option = $rcp_levels_db->get_meta( $level->id,'rcpbp_message_option');

		$message_option = $message_option[0];
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label  for="rcp-role"><?php _e( 'Allow Messaging?', 'rcpbp' ); ?></label>
			</th>
			<td>
				<select style="max-width: 106px" name="rcpbp-message-option" type="number" id="rcpbp-message-option">
					<?php if ($message_option === 'Deny') {
						echo '<option>Allow</option><option selected="selected">Deny</option>';
					} else {
						echo '<option selected="selected">Allow</option><option>Deny</option>';
					}  ?>

				</select>
				<p class="description"><?php _e( 'Number of groups allowed to create (-1 for unlimited)', 'rcpbp' ); ?></p>
			</td>
		</tr>
		<?php
	}
	public function subscription_message_option_save ($subscription_id, $args) {
		//get the global
		global $rcp_levels_db;
		// remove the old data
		$rcp_levels_db->delete_meta( $subscription_id, 'rcpbp_message_option' );
		// update with the new data
		$rcp_levels_db->add_meta( $subscription_id, 'rcpbp_message_option', $args['rcpbp-message-option'] );
	}
}