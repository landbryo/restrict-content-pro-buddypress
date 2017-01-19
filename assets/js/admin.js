jQuery(document).ready(function($){

	var restriction_control        = $('#rcp-restrict-by');
	var member_type_control        = $('#rcp-metabox-field-member-types');
	var group_control              = $('#rcp-metabox-field-groups');
	var role_control               = $('#rcp-metabox-field-role');

	var Settings_Controls = {
		prepare_type: function(type) {
			if ('unrestricted' === type) {
				group_control.hide();
				member_type_control.hide();
			}

			if ('registered-users' === type) {
				group_control.show();
				member_type_control.show();
			}

			if ('subscription-level' === type) {
				group_control.show();
				member_type_control.show();
			}

			if ('access-level' === type) {
				group_control.show();
				member_type_control.show();
			}
		}
	};

	var restriction_type = restriction_control.val();
	Settings_Controls.prepare_type(restriction_type);

	// restrict content metabox
	restriction_control.on('change', function() {
		var type = $(this).val();
		Settings_Controls.prepare_type(type);
	});

	group_control.insertAfter(role_control);
	member_type_control.insertAfter(role_control);


});