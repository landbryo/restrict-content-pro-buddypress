<?php
/**
 * Adds custom fields to category edit screens
 *
 * These options are for restricting content within categories
 *
 * @package     Restrict Content Pro - BuddyPress
 * @subpackage  Admin/Categories
 * @since       1.1.2
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Setup actions for taxonomy restricted fields
 *
 * @since 1.1.2
 * @return void
 */
function rcpbp_setup_taxonomy_edit_fields() {
	$taxonomies = rcp_get_restricted_taxonomies();

	foreach( $taxonomies as $taxonomy ) {
		add_action( "{$taxonomy}_edit_form_fields", 'rcpbp_term_edit_meta_fields', 11 );
		add_action( "{$taxonomy}_add_form_fields", 'rcpbp_term_add_meta_fields', 11 );
	}
}
add_action( 'admin_init', 'rcpbp_setup_taxonomy_edit_fields' );

/**
 * Add restriction options to the edit term page
 *
 * @access      public
 * @since       1.1.2
 * @return      void
 */
function rcpbp_term_edit_meta_fields( $term ) {

	$all_member_types = apply_filters( 'rcpbp_restricted_member_types', bp_get_member_types( array(), 'objects' ) );

	if ( bp_is_active( 'groups' ) ) {
		$all_groups = apply_filters( 'rcpbp_restricted_groups', groups_get_groups( 'show_hidden=true' ) );
	}

	// retrieve the existing value(s) for this meta field. This returns an array
	$term_meta = rcp_get_term_restrictions( $term->term_id );
	$member_types = isset( $term_meta['member_types'] ) ? array_map( 'sanitize_text_field', $term_meta['member_types'] ) : array();
	$groups = isset( $term_meta['groups'] ) ? array_map( 'sanitize_text_field', $term_meta['groups'] ) : array();
	?>

	<?php if ( ! empty( $all_member_types ) ) : ?>
		<tr class="rcp-metabox-field">
			<th scope="row"><?php _e( 'Member Type', 'rcpbp' ); ?></th>
			<td>
				<?php foreach ( $all_member_types as $name => $member_type ) : ?>
					<label for="rcp_category_meta[member_types][<?php echo esc_attr( $name ); ?>]">
						<input type="checkbox" value="<?php echo esc_attr( $name ); ?>" <?php checked( true, in_array( $name, (array) $member_types ) ); ?> name="rcp_category_meta[member_types][<?php echo esc_attr( $name ); ?>][]" id="rcp_category_meta[member_types][<?php echo esc_attr( $name ); ?>]" />&nbsp;
						<?php echo esc_html( $member_type->labels['name'] ); ?>
					</label><br />
				<?php endforeach; ?>
				<span class="description"><?php _e( 'Member types allowed to view content in this category. Leave unchecked for all.', 'restrict-content-pro-buddypress' ); ?></span>
			</td>
		</tr>
	<?php endif; ?>

	<?php if ( ! empty( $all_groups['groups'] ) ) : ?>
		<tr class="rcp-metabox-field">
			<th scope="row"><?php _e( 'Groups', 'rcpbp' ); ?></th>
			<td>
				<div class="rcpbp-restricted-groups-container">
					<?php foreach ( $all_groups['groups'] as $group ) : ?>
						<label for="rcp_category_meta[groups][<?php echo absint( $group->id ); ?>]">
							<input type="checkbox" value="<?php echo absint( $group->id ); ?>" <?php checked( true, in_array( $group->id, (array) $groups ) ); ?> name="rcp_category_meta[groups][<?php echo absint( $group->id ); ?>][]" id="rcp_category_meta[groups][<?php echo absint( $group->id ); ?>]" />&nbsp;
							<?php echo esc_html( $group->name ); ?>
						</label><br />
					<?php endforeach; ?>
				</div>
				<span class="description"><?php _e( 'Member of these groups are allowed to view content in this category. Leave unchecked for all.', 'restrict-content-pro-buddypress' ); ?></span>
			</td>
		</tr>
	<?php endif;
}


/**
 * Add restriction options to the add term page
 *
 * @param string $taxonomy
 *
 * @access      public
 * @since       1.1.2
 * @return      void
 */
function rcpbp_term_add_meta_fields( $taxonomy ) {
	$all_member_types = apply_filters( 'rcpbp_restricted_member_types', bp_get_member_types( array(), 'objects' ) );

	if ( bp_is_active( 'groups' ) ) {
		$all_groups = apply_filters( 'rcpbp_restricted_groups', groups_get_groups( 'show_hidden=true&per_page=999' ) );
	}

	?>

	<?php if ( ! empty( $all_member_types ) ) : ?>
		<div class="form-field">
			<p><?php _e( 'Member Type', 'rcpbp' ); ?></p>
			<?php foreach ( $all_member_types as $name => $member_type ) : ?>
				<label for="rcp_category_meta[member_types][<?php echo esc_attr( $name ); ?>]">
					<input type="checkbox" value="<?php echo esc_attr( $name ); ?>" name="rcp_category_meta[member_types][<?php echo esc_attr( $name ); ?>][]" id="rcp_category_meta[member_types][<?php echo esc_attr( $name ); ?>]" />&nbsp;
					<?php echo esc_html( $member_type->labels['name'] ); ?>
				</label>
			<?php endforeach; ?>
			<span class="description"><?php _e( 'Member types allowed to view content in this category. Leave unchecked for all.', 'restrict-content-pro-buddypress' ); ?></span>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $all_groups['groups'] ) ) : ?>
		<div class="form-field">
			<p><?php _e( 'Groups', 'rcpbp' ); ?></p>
			<div class="rcpbp-restricted-groups-container">
				<?php foreach ( $all_groups['groups'] as $group ) : ?>
					<label for="rcp_category_meta[groups][<?php echo absint( $group->id ); ?>]">
						<input type="checkbox" value="<?php echo absint( $group->id ); ?>" name="rcp_category_meta[groups][<?php echo absint( $group->id ); ?>][]" id="rcp_category_meta[groups][<?php echo absint( $group->id ); ?>]" />&nbsp;
						<?php echo esc_html( $group->name ); ?>
					</label>
				<?php endforeach; ?>
			</div>
			<span class="description"><?php _e( 'Member of these groups are allowed to view content in this category. Leave unchecked for all.', 'restrict-content-pro-buddypress' ); ?></span>
		</div>
	<?php endif;
}

/**
 * Save our custom term meta
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 *
 * @access      public
 * @since       1.1.2
 * @return      void
 */
function rcpbp_save_term_meta( $term_id, $tt_id, $taxonomy ) {

	if ( empty( $_POST['rcp_edit_term'] ) || ! wp_verify_nonce( $_POST['rcp_edit_term'], 'rcp_edit_term' ) ) {
		return;
	}

	$restricted_taxonomies = rcp_get_restricted_taxonomies();
	if ( ! in_array( $taxonomy, $restricted_taxonomies ) ) {
		return;
	}

	$term_meta = rcp_get_term_restrictions( $term_id );

	if( ! empty( $_POST['rcp_category_meta']['member_types'] ) ) {
		$term_meta['member_types'] = array_map( 'sanitize_text_field', array_keys( $_POST['rcp_category_meta']['member_types'] ) );
	}

	if( ! empty( $_POST['rcp_category_meta']['groups'] ) ) {
		$term_meta['groups'] = array_map( 'absint', array_keys( $_POST['rcp_category_meta']['groups'] ) );
	}

	if ( function_exists( 'update_term_meta' ) ) {
		if ( ! empty( $term_meta ) ) {
			update_term_meta( $term_id, 'rcp_restricted_meta', $term_meta );
		} else {
			delete_term_meta( $term_id, 'rcp_restricted_meta' );
		}
		// remove deprecated data
		delete_option( "rcp_category_meta_$term_id" );
	} else {
		// fallback to older method of handling term meta
		update_option( "rcp_category_meta_$term_id", $term_meta );
	}

}
add_action( 'edited_term', 'rcpbp_save_term_meta', 10, 3 );
add_action( 'created_term', 'rcpbp_save_term_meta', 10, 3 );