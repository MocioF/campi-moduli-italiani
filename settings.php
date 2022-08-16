<?php
/**
 * Settings
 *
 * @package campi-moduli-italiani
 * @author       Giuseppe Foti
 * @copyright    Giuseppe Foti
 * @license      GPL-2.0+
 *
 * @since 1.0.0
 *
 * From this file is it possible to deactivate specific modules
 * by setting GCMI_USE_[] costants to false.
 */

require_once plugin_dir_path( GCMI_PLUGIN ) . 'admin/class-gcmi-activator.php';
require_once plugin_dir_path( GCMI_PLUGIN ) . 'includes/cron.php';

if ( is_admin() ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'admin/admin.php';
}

/* configurazione tipo campi utilizzati */
if ( ! defined( 'GCMI_USE_COMUNE' ) ) {
	define( 'GCMI_USE_COMUNE', true );
}

if ( ! defined( 'GCMI_USE_CF' ) ) {
	define( 'GCMI_USE_CF', true );
}

if ( ! defined( 'GCMI_USE_STATO' ) ) {
	define( 'GCMI_USE_STATO', true );
}

if ( ! defined( 'GCMI_USE_FORMSIGN' ) ) {
	define( 'GCMI_USE_FORMSIGN', true );
}

/* configurazione integrazioni utilizzate */
if ( ! defined( 'GCMI_USE_CF7_INTEGRATION' ) ) {
	define( 'GCMI_USE_CF7_INTEGRATION', true );
}

if ( ! defined( 'GCMI_USE_WPFORMS_INTEGRATION' ) ) {
	define( 'GCMI_USE_WPFORMS_INTEGRATION', true );
}

/* fine sezione editabile */

if ( GCMI_USE_COMUNE === true ) {
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/class-gcmi-comune-shortcode.php';
	require_once plugin_dir_path( GCMI_PLUGIN ) . 'modules/comune/comune-shortcode.php';

	add_action( 'wp_ajax_the_ajax_hook_prov', 'GCMI_COMUNE::gcmi_province' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_prov', 'GCMI_COMUNE::gcmi_province' );
	add_action( 'wp_ajax_the_ajax_hook_comu', 'GCMI_COMUNE::gcmi_comuni' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_comu', 'GCMI_COMUNE::gcmi_comuni' );
	add_action( 'wp_ajax_the_ajax_hook_targa', 'GCMI_COMUNE::gcmi_targa' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_targa', 'GCMI_COMUNE::gcmi_targa' );
	add_action( 'wp_ajax_the_ajax_hook_info', 'GCMI_COMUNE::gcmi_showinfo' );
	add_action( 'wp_ajax_nopriv_the_ajax_hook_info', 'GCMI_COMUNE::gcmi_showinfo' );

	add_action( 'wp_enqueue_scripts', 'GCMI_COMUNE::gcmi_register_scripts' );
}


function gcmi_load_integrations() {
	if ( GCMI_USE_CF7_INTEGRATION === true ) {
		if ( class_exists( 'WPCF7' ) ) {
			require_once plugin_dir_path( GCMI_PLUGIN ) . 'integrations/contact-form-7/contact-form-7-integrations.php';
		}
	}

	if ( GCMI_USE_WPFORMS_INTEGRATION === true ) {
		if ( class_exists( 'WPForms' ) ) {
			require_once plugin_dir_path( GCMI_PLUGIN ) . 'integrations/wpforms/wpforms-integration.php';
		}
	}
}
add_action( 'plugins_loaded', 'gcmi_load_integrations' );

add_action( 'admin_init', 'gcmi_upgrade', 10, 0 );

/**
 * Updates the plugin version number in the database
 *
 * @since 1.0.0
 */
function gcmi_upgrade() {
	$old_ver = get_option( 'gcmi_plugin_version', '0' );
	$new_ver = GCMI_VERSION;

	if ( $old_ver === $new_ver ) {
		return;
	}

	do_action( 'gcmi_upgrade', $new_ver, $old_ver );
	update_option( 'gcmi_plugin_version', $new_ver );
}


/**
 * Adds extra links to the plugin activation page
 *
 * @param  array  $meta   Extra meta links.
 * @param  string $file   Specific file to compare against the base plugin.
 * @return array          Return the meta links array
 */
function get_extra_meta_links( $meta, $file ) {
	if ( GCMI_PLUGIN_BASENAME === $file ) {
		$plugin_page = admin_url( 'admin.php?page=gcmi' );
		$meta[]      = "<a href='https://wordpress.org/support/plugin/campi-moduli-italiani/' target='_blank' title'" . __( 'Support', 'campi-moduli-italiani' ) . "'>" . __( 'Support', 'campi-moduli-italiani' ) . '</a>';
		$meta[]      = "<a href='https://wordpress.org/support/plugin/campi-moduli-italiani/reviews#new-post' target='_blank' title='" . __( 'Leave a review', 'campi-moduli-italiani' ) . "'><i class='gcmi-stars'><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg><svg xmlns='http://www.w3.org/2000/svg' width='15' height='15' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round' class='feather feather-star'><polygon points='12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2'/></svg></i></a>";
	}
	return $meta;
}

/**
 * Adds styles to admin head to allow for stars animation and coloring
 */
function add_star_styles() {
	global $pagenow;
	if ( 'plugins.php' === $pagenow ) {?>
		<style>
			.gcmi-stars{display:inline-block;color:#ffb900;position:relative;top:3px}
			.gcmi-stars svg{fill:#ffb900}
			.gcmi-stars svg:hover{fill:#ffb900}
			.gcmi-stars svg:hover ~ svg{fill:none}
		</style>
		<?php
	}
}

add_filter( 'plugin_row_meta', 'get_extra_meta_links', 10, 2 );
add_action( 'admin_head', 'add_star_styles' );

register_activation_hook( GCMI_PLUGIN, array( GCMI_Activator::class, 'activate' ) );
register_deactivation_hook( GCMI_PLUGIN, array( GCMI_Activator::class, 'deactivate' ) );

/**
 * Display plugin upgrade notice to users
 */
function prefix_plugin_update_message( $data, $response ) {
	if ( isset( $data['upgrade_notice'] ) ) {
		printf(
			'<div class="update-message">%s</div>',
			wpautop( $data['upgrade_notice'] )
		);
	}
}
add_action( 'in_plugin_update_message-campi-moduli-italiani/campi-moduli-italiani.php', 'prefix_plugin_update_message', 10, 2 );

/**
 * Display plugin upgrade notice to users on multisite installations
 */
function prefix_ms_plugin_update_message( $file, $plugin ) {
	if ( is_multisite() && version_compare( $plugin['Version'], $plugin['new_version'], '<' ) ) {
		$wp_list_table = _get_list_table( 'WP_Plugins_List_Table' );
		printf(
			'<tr class="plugin-update-tr"><td colspan="%s" class="plugin-update update-message notice inline notice-warning notice-alt"><div class="update-message"><h4 style="margin: 0; font-size: 14px;">%s</h4>%s</div></td></tr>',
			$wp_list_table->get_column_count(),
			$plugin['Name'],
			wpautop( $plugin['upgrade_notice'] )
		);
	}
}
add_action( 'after_plugin_row_wp-campi-moduli-italiani/campi-moduli-italiani.php', 'prefix_ms_plugin_update_message', 10, 2 );

/**
 * Show error in front end
 *
 * @param WP_Error $gcmi_error
 * @retur void
 * @since 2.1.0
 */
function gcmi_show_error( $gcmi_error ) {
	if ( is_wp_error( $gcmi_error ) ) {
		foreach ( $gcmi_error->get_error_messages() as $error ) {
			$output  = '<div class="gcmi_error notice notice-error is-dismissible">';
			$output .= '<strong>ERROR: ' . $gcmi_error->get_error_code() . '</strong><br/>';
			$output .= $error . '<br/>';
			$output .= '</div>';

			$allowed_html = array(
				'div'    => array(
					'class' => array(),
				),
				'strong' => array(),
				'br'     => array(),
			);
			echo wp_kses( $output, $allowed_html );
		}
	}
}
