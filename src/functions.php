<?php
if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( ! function_exists( 'xs_name' ) ) {
	/**
	 * Format the name
	 *
	 * @param string $name
	 * @return string
	 */

	function sx_name( string $name ): string
	{
		return strtolower( SX_PREFIX . '_' . $name );
	}
}


if( ! function_exists('sx_validate_fields' )) {
	/**
	 * Validate fields
	 *
	 * @param array $fields
	 * @return array
	 */
	function sx_validate_fields( array $fields ): array
	{
		return array_filter($fields, function( $field) {
			return $field->enabled ?? false;
		});
	}
}

if( ! function_exists( 'sx_select' ) ) {
	/**
	 * Create a new select field
	 *
	 * @return SX_Select
	 */
	function sx_select(): SX_Select
	{
		return new SX_Select();
	}
}
if( ! function_exists( 'sx_section' ) ) {
	/**
	 * Create a new section field
	 *
	 * @return SX_Section
	 */
	function sx_section(): SX_Section
	{
		return new SX_Section();
	}
}
if( ! function_exists( 'sx_checkbox' ) ) {
	/**
	 * Create a new checkbox field
	 *
	 * @return SX_Checkbox
	 */
	function sx_checkbox(): SX_Checkbox
	{
		return new SX_Checkbox();
	}
}
if( ! function_exists( 'sx_textarea' ) ) {
	/**
	 * Create a new textarea field
	 *
	 * @return SX_Textarea
	 */
	function sx_textarea(): SX_Textarea
	{
		return new SX_Textarea();
	}
}
if( ! function_exists( 'sx_switch' ) ) {
	/**
	 * Create a new switch field
	 *
	 * @return SX_Switch
	 */
	function sx_switch(): SX_Switch
	{
		return new SX_Switch();
	}
}
if( ! function_exists( 'sx_color' ) ) {
	/**
	 * Create a new color field
	 *
	 * @return SX_Color
	 */
	function sx_color(): SX_Color
	{
		return new SX_Color();
	}
}
if( ! function_exists( 'sx_password' ) ) {
	/**
	 * Create a new password field
	 *
	 * @return SX_Password
	 */
	function sx_password(): SX_Password
	{
		return new SX_Password();
	}
}
if( ! function_exists( 'sx_text' ) ) {
	/**
	 * Create a new text field
	 *
	 * @return SX_Text
	 */
	function sx_text(): SX_Text
	{
		return new SX_Text();
	}
}
if( ! function_exists( 'sx_custom' ) ) {
	/**
	 * Create a new custom field
	 *
	 * @return SX_Custom
	 */
	function sx_custom(): SX_Custom
	{
		return new SX_Custom();
	}
}
if( ! function_exists( 'sx_repeat' ) ) {
	/**
	 * Create a new repeat field
	 *
	 * @return SX_Repeat
	 */
	function sx_repeat(): SX_Repeat
	{
		return new SX_Repeat();
	}
}
if( ! function_exists( 'sx_tab' ) ) {
	/**
	 * Create a new tab field
	 *
	 * @return SX_Tab
	 */
	function sx_tab(): SX_Tab
	{
		return new SX_Tab();
	}
}



function sx_get_roles()
{
	$wp_roles = wp_roles()->roles;
	$roles = [];

	foreach( $wp_roles as $key => $role ) {
		$roles[$key] = $role['name'];
	}

	return $roles;
}

function sx_get_users()
{
	$users = get_users();
	$users_list = [];

	foreach( $users as $user ) {
		$users_list[$user->ID] = $user->display_name;
	}

	return $users_list;
}

//function sx_get_adminbar()
//{
//	global $wp_admin_bar;
//var_dump($wp_admin_bar );
//	$adminbar = [];
//	foreach( $wp_admin_bar->get_nodes() as $node ) {
//		$adminbar[$node->id] = $node->title;
//	}
//
//	return $adminbar;
//}


function sx_for_selectable( ...$selects )
{
	$data = [];
	$source = [];
	foreach( $selects as $select ) {
		switch ( $select ) {
			case 'role':
				$source = sx_get_roles();
				break;
			case 'user':
				$source = sx_get_users();
				break;
//			case 'adminbar':
//				$source = sx_get_adminbar();
//				break;
		}

		$updated = [];
		foreach( $source as $key => $value ) {
			$updated[ $select . '__' . $key] = ucfirst($select) . ': ' . $value;
		}

		$data = array_merge( $data, $updated );
	}

	// order by value
	asort( $data );

	return $data;
}

function sx_is_allowed( $validate ) {
	$is_allowed = false;
	foreach( $validate as $item ) {
		// first check what source it is by splitting for the :
		$source = explode( '__', $item );
		$current_user = wp_get_current_user();

		$source_key = $source[0];
		$source_value = $source[1];

		switch ( $source_key ) {
			case 'role':
				// check if this user has the role
				$roles = $current_user->roles;
				if ( in_array( $source_value, $roles ) ) {
					$is_allowed = true;
				}
				break;
			case 'user':
				// check if this user is the user
				if ( $current_user->ID == $source_value ) {
					$is_allowed = true;
				}
				break;
		}
	}

	return $is_allowed;
}

function sx_setting( string $name, $default = null ) {
	$array = get_option( SX_OPTION_SLUG );

	return $array[$name] ?? $default;
}


 function sx_plugin_update_htaccess( $block, $rules ): void {
	$filesystem = sx_plugin_get_filesystem();
	if ( null === $filesystem ) {
		return;
	}

	$htaccess_file = sx_plugin_get_writable_htaccess_path( $filesystem );
	if ( null === $htaccess_file ) {
		return;
	}

	sx_plugin_cleanup_htaccess( $block );

	$original_contents = $filesystem->get_contents( $htaccess_file );
	if ( false === $original_contents ) {
		return;
	}

	$rules = explode( PHP_EOL, $rules );
	$rules = array_map( 'trim', $rules );

	$lines = array(
		$original_contents,
		'',
		sprintf( '# BEGIN %s', $block ),
	);

	$lines = array_merge( $lines, $rules );

	$lines[] = sprintf( '# END %s', $block );
	$lines[] = '';

	$filesystem->put_contents( $htaccess_file, implode( PHP_EOL, $lines ) );
}


function sx_plugin_cleanup_htaccess( $block ): void {
	$filesystem = sx_plugin_get_filesystem();
	if ( null === $filesystem ) {
		return;
	}

	$htaccess_file = sx_plugin_get_writable_htaccess_path( $filesystem );
	if ( null === $htaccess_file ) {
		return;
	}

	$htaccess_contents = $filesystem->get_contents( $htaccess_file );
	if ( false === $htaccess_contents ) {
		return;
	}

	$regex             = '/# BEGIN ' . preg_quote( $block, '/' ) . '.*?# END ' . preg_quote( $block, '/' ) . '/s';
	$htaccess_contents = preg_replace( $regex, "\n\n", $htaccess_contents );

	$htaccess_contents = preg_replace( "/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $htaccess_contents );
	$filesystem->put_contents( $htaccess_file, $htaccess_contents );

	sx_plugin_flush_rewrite_rules();
}

function sx_plugin_flush_rewrite_rules(): void {
	global $wp_rewrite;
	if ( $wp_rewrite instanceof WP_Rewrite ) {
		$wp_rewrite->flush_rules();
	}
}


function sx_plugin_get_filesystem(): ?WP_Filesystem_Base {
	if( ! function_exists( 'WP_Filesystem' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	if ( true !== WP_Filesystem() ) {
		return null;
	}

	global $wp_filesystem;

	return $wp_filesystem;
}

function sx_plugin_get_writable_htaccess_path( WP_Filesystem_Base $filesystem ): ?string {
	$htaccess_file = get_home_path() . '.htaccess';

	if ( ! $filesystem->exists( $htaccess_file ) || ! $filesystem->is_readable( $htaccess_file ) || ! $filesystem->is_writable( $htaccess_file ) ) {
		return null;
	}

	return $htaccess_file;
}

