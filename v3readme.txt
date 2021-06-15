### Changed hooks

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



### Deprecated functions

`UM()->get_template()` use `um_get_template()` instead and for getting template content without echo use `um_get_template_html()`
`UM()->locate_template()` use `um_locate_template()` instead


### Added constants:

UM_TEMPLATE_CONFLICT_TEST - for debugging custom templates
