<?php
/**
 * Updates from GitHub Releases.
 *
 * Uses the core "Update URI" mechanism (WordPress 5.8+): WordPress calls the
 * update_plugins_github.com filter for this plugin only, and never asks
 * wordpress.org about it. The newest GitHub release must contain an asset
 * named utm-parameter-forwarder.zip; the tag is the version (e.g. v1.1.0).
 *
 * Nothing about the site is sent to GitHub: the request carries only a
 * generic User-Agent, no site URL, no credentials.
 *
 * @package UTM_Parameter_Forwarder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Supplies update information to WordPress.
 */
final class UTMPF_Updater {

	const REPO       = 'felix-werner-landeseiten/utm_parms_landeseiten';
	const ASSET_NAME = 'utm-parameter-forwarder.zip';
	const CACHE_KEY  = 'utmpf_release_info';
	const CACHE_TTL  = 6 * HOUR_IN_SECONDS;
	const ERROR_TTL  = HOUR_IN_SECONDS;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'check_update' ), 10, 3 );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'clear_cache' ), 10, 0 );
	}

	/**
	 * Provide update data for this plugin.
	 *
	 * @param array|false $update      Existing update data.
	 * @param array       $plugin_data Plugin headers.
	 * @param string      $plugin_file Plugin basename.
	 * @return array|false
	 */
	public static function check_update( $update, $plugin_data, $plugin_file ) {
		if ( plugin_basename( UTMPF_FILE ) !== $plugin_file ) {
			return $update;
		}

		$release = self::get_release();
		if ( ! $release ) {
			return $update;
		}

		// WordPress itself compares this version with the installed one.
		return array(
			'id'           => 'https://github.com/' . self::REPO,
			'slug'         => UTMPF_SLUG,
			'plugin'       => $plugin_file,
			'version'      => $release['version'],
			'url'          => $release['url'],
			'package'      => $release['package'],
			'requires_php' => '7.4',
		);
	}

	/**
	 * Data for the "View details" popup.
	 *
	 * @param false|object|array $result Default result.
	 * @param string             $action API action.
	 * @param object             $args   Request arguments.
	 * @return false|object|array
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || ! is_object( $args ) || ! isset( $args->slug ) || UTMPF_SLUG !== $args->slug ) {
			return $result;
		}

		$release = self::get_release();
		if ( ! $release ) {
			return $result;
		}

		return (object) array(
			'name'          => 'UTM Parameter Forwarder',
			'slug'          => UTMPF_SLUG,
			'version'       => $release['version'],
			'author'        => 'Landeseiten',
			'homepage'      => 'https://github.com/' . self::REPO,
			'requires'      => '5.8',
			'requires_php'  => '7.4',
			'download_link' => $release['package'],
			'sections'      => array(
				'changelog' => nl2br( esc_html( $release['notes'] ) ),
			),
		);
	}

	/**
	 * Forget cached release data after any update.
	 */
	public static function clear_cache() {
		delete_site_transient( self::CACHE_KEY );
	}

	/**
	 * Latest release, validated and cached.
	 *
	 * @return array{version: string, package: string, url: string, notes: string}|null
	 */
	private static function get_release() {
		$cached = get_site_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return empty( $cached['version'] ) ? null : $cached;
		}

		$release = self::fetch_release();
		set_site_transient( self::CACHE_KEY, $release ? $release : array( 'version' => '' ), $release ? self::CACHE_TTL : self::ERROR_TTL );

		return $release;
	}

	/**
	 * Ask the GitHub API for the latest release.
	 *
	 * @return array|null
	 */
	private static function fetch_release() {
		$response = wp_safe_remote_get(
			'https://api.github.com/repos/' . self::REPO . '/releases/latest',
			array(
				'timeout'     => 10,
				'redirection' => 2,
				'user-agent'  => 'UTM-Parameter-Forwarder-Updater',
				'headers'     => array( 'Accept' => 'application/vnd.github+json' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $data ) || ! empty( $data['draft'] ) || ! empty( $data['prerelease'] ) || empty( $data['tag_name'] ) || ! is_string( $data['tag_name'] ) ) {
			return null;
		}

		$version = ltrim( $data['tag_name'], 'vV' );
		if ( 1 !== preg_match( '/^\d+\.\d+\.\d+$/', $version ) ) {
			return null;
		}

		// The package must be this repository's own release asset.
		$download_prefix = 'https://github.com/' . self::REPO . '/releases/download/';
		$package         = '';
		if ( ! empty( $data['assets'] ) && is_array( $data['assets'] ) ) {
			foreach ( $data['assets'] as $asset ) {
				if ( is_array( $asset ) && isset( $asset['name'], $asset['browser_download_url'] )
					&& self::ASSET_NAME === $asset['name']
					&& is_string( $asset['browser_download_url'] )
					&& 0 === strpos( $asset['browser_download_url'], $download_prefix ) ) {
					$package = $asset['browser_download_url'];
					break;
				}
			}
		}
		if ( '' === $package ) {
			return null;
		}

		$url = 'https://github.com/' . self::REPO . '/releases/tag/' . rawurlencode( $data['tag_name'] );

		return array(
			'version' => $version,
			'package' => esc_url_raw( $package ),
			'url'     => esc_url_raw( $url ),
			'notes'   => isset( $data['body'] ) && is_string( $data['body'] ) ? substr( $data['body'], 0, 20000 ) : '',
		);
	}
}
