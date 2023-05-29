### Synced with legacy date:

Core: 03.09.2022

MailChimp: 03.09.2022





### Changed handlers:

* Don't remove core pages on uninstall. There can be other content on the page


### Changed hooks

`um_admin_bulk_user_actions_hook`

Was structure:
$actions['um_verify_accounts'] = array( 'label' => __( 'Mark accounts as verified', 'um-verified' ) );
$actions['um_unverify_accounts'] = array( 'label' => __( 'Mark accounts as unverified', 'um-verified' ) );

Current structure:
$actions['um_verify_accounts'] => __( 'Mark accounts as verified', 'um-verified' )
$actions['um_unverify_accounts'] => __( 'Mark accounts as unverified', 'um-verified' )

`um_locate_template`

/**
  * UM hook
  *
  * @input_vars
  * [
  *    v2.0:[
  *       {"var":"$template","type":"string","desc":"Template locate"},
  *       {"var":"$template_name","type":"string","desc":"Template Name"},
  *       {"var":"$path","type":"string","desc":"Template Path at server"}
  *    ],
  *    v3.0:[
  *       {"var":"$template","type":"string","desc":"Template locate"},
  *       {"var":"$template_name","type":"string","desc":"Template Name"},
  *       {"var":"$module","type":"string","desc":"Module slug"},
  *       {"var":"$template_path","type":"string","desc":"Template Path at server"}
  *    ],
  * ]
  * @change_log
  * ["Since: 2.0", "Modified: 3.0"]
  * @example
  * <?php
  * add_filter( 'um_locate_template', 'my_locate_template', 10, 4 );
  * function my_locate_template( $template, $template_name, $module, $template_path ) {
  *     // your code here
  *     return $template;
  * }
  * ?>
  */

`um_get_template`

/**
 * UM hook
 *
 * @input_vars
 * [
 *    v2.0:[
 *       {"var":"$located","type":"string","desc":"template Located"},
 *       {"var":"$template_name","type":"string","desc":"Template Name"},
 *       {"var":"$path","type":"string","desc":"Template Path at server"},
 *       {"var":"$t_args","type":"array","desc":"Template Arguments"}
 *    ],
 *    v3.0:[
 *       {"var":"$template","type":"string","desc":"Template Located"},
 *       {"var":"$template_name","type":"string","desc":"Template Name"},
 *       {"var":"$args","type":"array","desc":"Template Arguments"},
 *       {"var":"$module","type":"string","desc":"Module slug"},
 *       {"var":"$template_path","type":"string","desc":"Template Path at server in theme"},
 *       {"var":"$default_path","type":"string","desc":"Template Path at server in plugin folder"}
 *    ],
 * ]
 * @change_log
 * ["Since: 2.0", "Modified: 3.0"]
 * @example
 * <?php
 * add_filter( 'um_get_template', 'my_get_template', 10, 6 );
 * function my_get_template( $template, $template_name, $args, $module, $template_path, $default_path ) {
 *     // your code here
 *     return $template;
 * }
 * ?>
 */

`um_before_template_part`

/**
 * UM hook
 *
 * @input_vars
 * [
 *    v2.0:[
 *       {"var":"$template_name","type":"string","desc":"Template Name"},
 *       {"var":"$path","type":"string","desc":"Template Path at server"},
 *       {"var":"$located","type":"string","desc":"template Located"},
 *       {"var":"$t_args","type":"array","desc":"Template Arguments"}
 *    ],
 *    v3.0:[
 *       {"var":"$template_name","type":"string","desc":"Template Name"},
 *       {"var":"$located","type":"string","desc":"Template Path at server"},
 *       {"var":"$module","type":"string","desc":"Module slug"},
 *       {"var":"$args","type":"array","desc":"Template Arguments"},
 *       {"var":"$template_path","type":"string","desc":"Template Path at server in theme"}
 *    ],
 * ]
 * @change_log
 * ["Since: 2.0", "Modified: 3.0"]
 * @example
 * <?php
 * add_action( 'um_before_template_part', 'my_before_template_part', 10, 5 );
 * function my_before_template_part( $template_name, $located, $module, $args, $template_path ) {
 *     // your code here
 * }
 * ?>
 */

`um_after_template_part`

/**
 * UM hook
 *
 * @input_vars
 * [
 *    v2.0:[
 *       {"var":"$template_name","type":"string","desc":"Template Name"},
 *       {"var":"$path","type":"string","desc":"Template Path at server"},
 *       {"var":"$located","type":"string","desc":"template Located"},
 *       {"var":"$t_args","type":"array","desc":"Template Arguments"}
 *    ],
 *    v3.0:[
 *       {"var":"$template_name","type":"string","desc":"Template Name"},
 *       {"var":"$located","type":"string","desc":"Template Path at server"},
 *       {"var":"$module","type":"string","desc":"Module slug"},
 *       {"var":"$args","type":"array","desc":"Template Arguments"},
 *       {"var":"$template_path","type":"string","desc":"Template Path at server in theme"}
 *    ],
 * ]
 * @change_log
 * ["Since: 2.0", "Modified: 3.0"]
 * @example
 * <?php
 * add_action( 'um_after_template_part', 'my_after_template_part', 10, 5 );
 * function my_after_template_part( $template_name, $located, $module, $args, $template_path ) {
 *     // your code here
 * }
 * ?>
 */


### Deprecated hooks

'um_before_form_is_loaded', 'um_before_password_form_is_loaded' - action hook for the lostpassword form, use 'um_pre_password_shortcode' for both
'um_reset_password_shortcode_args_filter' - use 'shortcode_atts_ultimatemember_password' filter's hook
'um_reset_password_errors_hook' - use action hook 'um_lostpassword_errors_hook' instead
'um_reset_password_process_hook' - use action hook 'um_before_send_lostpassword_link' instead
'um_change_password_errors_hook' - use action hook 'um_resetpass_errors_hook' instead
'um_change_password_process_hook' - use action hook 'um_before_changing_user_password' instead
'um_reset_password_page_hidden_fields' - use filter's hook `um_lostpassword_form_args` instead and add the hiddens to it
'um_after_password_reset_fields' - use filter's hook `um_lostpassword_form_args` instead and add the fields to it
'um_change_password_page_hidden_fields' - use filter's hook `um_resetpass_form_args` instead and add the hiddens to it

'um_change_password_form' - action hook for the resetpass form, please edit 'reset-password.php' template and add the content below the form
'um_after_form_fields' - action hook for the resetpass form, please edit 'reset-password.php' template and add the content below the form
'um_reset_password_form' - action hook for the lostpassword form, please edit lostpassword.php template file instead
'um_after_form_fields' - action hook for the lostpassword form, please edit lostpassword.php template file instead


'um_when_status_is_set' - use action hook 'um_before_user_status_is_set' instead
'um_after_user_status_is_changed_hook' - use action hook 'um_after_user_status_is_changed' instead
'um_activate_url' to 'um_activate_url_base'
"um_admin_custom_hook_{$action}" - use filter hook "um_handle_bulk_actions-users-{$current_action}" instead with setting redirect attribute after the action
'um_admin_user_action_hook' - fully deprecated. Moved to the WordPress native bulk actions and handler for them
"um_admin_user_action_{$bulk_action}_hook" - fully deprecated. Moved to the WordPress native bulk actions and handler for them

'um_form_official_classes__hook' - fully deprecated for the Account forms.
'um_account_shortcode_args_filter' - deprecated. Use 'shortcode_atts_ultimatemember_account' instead of 'um_account_shortcode_args_filter' for filter arguments.
'um_before_account_form_is_loaded' - fully deprecated. Use 'um_pre_account_shortcode' instead of it.
### Deprecated functions

`UM()->get_template()` use `um_get_template()` instead and for getting template content without echo use `um_get_template_html()` (remove since 1 year after official v3 release)
`UM()->locate_template()` use `um_locate_template()` instead (remove since 1 year after official v3 release)
`UM()->fonticons()` and class file. There is FA json file with all icons data.

`um_dynamic_modal_content` AJAX action for getting modal content. Please make the separate handlers for displaying modal content.

### Deprecated variables

`UM()->is_filtering` is deprecated. It's not useful since 2.1.0 where the member directories functionality has been deprecated.

$GLOBALS['ultimatemember'] is deprecated. We don't use global $ultimatemember since 2.0


### Deprecated templates:

password-reset.php -> lostpassword.php
password-change.php -> reset-password.php

### Deleted after deprecate

`UM()->members()` has been deprecated since 2.1 and now is deleted please use `UM()->member_directory()` instead

### Deprecated options:

* `disable_admin_reset_password_limit` - UM()->options()->get( 'disable_admin_reset_password_limit' ). Admin reset password limit is disabled by default.

### Added plain email templates:

* emails/plain/reset-password.php
* emails/plain/password-changed.php

### Changed templates:

* email/resetpw_email.php -> emails/reset-password.php
* email/changedpw_email.php -> emails/password-changed.php

### Added constants:

UM_TEMPLATE_CONFLICT_TEST - for debugging custom templates


## Login Form

* Added shortcode [ultimatemember_login] for displaying login form.
  Shortcode attributes:
  'login_button'       => (string) text for the login button label, `__( 'Log In', 'ultimate-member' )` by default,
  'show_remember'      => (bool) 1||0, `true` by default,
  'show_forgot'        => (bool) 1||0, `true` by default,
  'login_redirect'     => (string) redirect_profile||redirect_url||refresh||redirect_admin, Empty by default and getting from logged in user's priority role,
  'login_redirect_url' => (string) If login_redirect="redirect_url" then an attribute using for custom URL redirect,

* Legacy shortcode for login form [ultimatemember form_id="{login_form_id}"] that just populates attributes to ultimatemember_login callback function.

* The possible hooks with v3 login to customize form content:
  'login_form_top'    - just after the <form> tag is opened
  'login_form_middle' - after username and password fields before the sumbit button
  'login_form_bottom' - just before the <form> tag is closed

* wpnonce has been removed from a login form

### Deprecated templates:



## Form Builder:

#### Fields meta:
* '_help' to '_description'
* removed '_visibility' and '_public' + '_roles' for fields on the registration form
* '_intervals' to '_step' for the time-type field
* 'multiselect' field-type to the 'dropdown' with active '_is_multi' setting
* '_height' to '_rows' for the textarea-type field



## Users List Table:

* Removed links with statuses, but added dropdown filter for that
* Removed custom bulk actions, but added them to the WordPress native bulk-actions list
* Reviewed bulk actions handlers and rewritten User class for handle changing statuses




### Member Directory
* '_um_view_types' deprecated
* '_um_default_view' changed to '_um_view_type'
