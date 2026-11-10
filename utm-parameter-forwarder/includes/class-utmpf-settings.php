<?php
/**
 * Settings: storage, sanitization and the admin page (Settings > UTM Forwarder).
 *
 * @package UTM_Parameter_Forwarder
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles the plugin options.
 */
final class UTMPF_Settings {

	const OPTION     = 'utmpf_settings';
	const PAGE       = 'utmpf-settings';
	const MAX_PARAMS = 30;

	/**
	 * Register hooks.
	 */
	public static function init() {
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( UTMPF_FILE ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * Parameters forwarded out of the box.
	 *
	 * @return string[]
	 */
	public static function default_params() {
		return array(
			// Standard UTM parameters (Google Analytics / Tag Manager, all ad networks).
			'utm_source',
			'utm_medium',
			'utm_campaign',
			'utm_term',
			'utm_content',
			'utm_id',
			// Google Ads.
			'gclid',
			'gbraid',
			'wbraid',
			'gad_source',
			// Meta (Facebook / Instagram).
			'fbclid',
			// LinkedIn.
			'li_fat_id',
		);
	}

	/**
	 * Default option values.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			'params'  => self::default_params(),
			'persist' => true,
		);
	}

	/**
	 * Current settings, always in a valid shape.
	 *
	 * @return array{params: string[], persist: bool}
	 */
	public static function get() {
		$saved = get_option( self::OPTION, array() );
		if ( ! is_array( $saved ) ) {
			$saved = array();
		}
		$settings = wp_parse_args( $saved, self::defaults() );

		$params = self::clean_param_list( $settings['params'] );

		return array(
			'params'  => $params ? $params : self::default_params(),
			'persist' => (bool) $settings['persist'],
		);
	}

	/**
	 * Keep only safe parameter names: letters, digits, "_" and "-", max 64 chars.
	 *
	 * @param mixed $list Array or newline/comma separated string.
	 * @return string[]
	 */
	public static function clean_param_list( $list ) {
		if ( is_string( $list ) ) {
			$list = preg_split( '/[\s,]+/', $list );
		}
		if ( ! is_array( $list ) ) {
			return array();
		}

		$clean = array();
		foreach ( $list as $name ) {
			if ( ! is_string( $name ) ) {
				continue;
			}
			$name = strtolower( trim( $name ) );
			if ( 1 === preg_match( '/^[a-z0-9_-]{1,64}$/', $name ) ) {
				$clean[ $name ] = $name;
			}
		}

		return array_slice( array_values( $clean ), 0, self::MAX_PARAMS );
	}

	/**
	 * Register the option with a strict sanitize callback.
	 */
	public static function register() {
		register_setting(
			'utmpf',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize' ),
				'default'           => self::defaults(),
				'show_in_rest'      => false,
			)
		);
	}

	/**
	 * Sanitize submitted settings.
	 *
	 * @param mixed $input Raw input.
	 * @return array
	 */
	public static function sanitize( $input ) {
		if ( ! is_array( $input ) ) {
			$input = array();
		}

		$params = self::clean_param_list( isset( $input['params'] ) ? $input['params'] : '' );
		if ( ! $params ) {
			$params = self::default_params();
			add_settings_error( self::OPTION, 'utmpf_params', __( 'No valid parameter names were entered, so the defaults were restored.', 'utm-parameter-forwarder' ) );
		}

		return array(
			'params'  => $params,
			'persist' => ! empty( $input['persist'] ),
		);
	}

	/**
	 * Add the page under Settings.
	 */
	public static function add_page() {
		add_options_page(
			__( 'UTM Parameter Forwarder', 'utm-parameter-forwarder' ),
			__( 'UTM Forwarder', 'utm-parameter-forwarder' ),
			'manage_options',
			self::PAGE,
			array( __CLASS__, 'render_page' )
		);
	}

	/**
	 * "Settings" link on the Plugins screen.
	 *
	 * @param string[] $links Existing links.
	 * @return string[]
	 */
	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=' . self::PAGE );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'utm-parameter-forwarder' ) . '</a>' );
		return $links;
	}

	/**
	 * Render the settings page.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = self::get();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'UTM Parameter Forwarder', 'utm-parameter-forwarder' ); ?></h1>
			<p>
				<?php esc_html_e( 'When a visitor arrives with any of the parameters below in the URL, they are appended to every internal link on the page, so the next page (and its forms) receives them too.', 'utm-parameter-forwarder' ); ?>
			</p>
			<form method="post" action="options.php">
				<?php settings_fields( 'utmpf' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="utmpf-params"><?php esc_html_e( 'Parameters to forward', 'utm-parameter-forwarder' ); ?></label></th>
						<td>
							<textarea id="utmpf-params" name="<?php echo esc_attr( self::OPTION ); ?>[params]" rows="12" cols="40" class="code"><?php echo esc_textarea( implode( "\n", $settings['params'] ) ); ?></textarea>
							<p class="description">
								<?php
								printf(
									/* translators: %d: maximum number of parameters. */
									esc_html__( 'One per line. Allowed characters: a-z, 0-9, "_" and "-". Maximum %d entries.', 'utm-parameter-forwarder' ),
									(int) self::MAX_PARAMS
								);
								?>
							</p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Remember during visit', 'utm-parameter-forwarder' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[persist]" value="1" <?php checked( $settings['persist'] ); ?> />
								<?php esc_html_e( 'Keep the parameters in the browser tab (sessionStorage) so they are still added if the visitor reaches a page without them, e.g. through a menu link that was rendered later.', 'utm-parameter-forwarder' ); ?>
							</label>
							<p class="description"><?php esc_html_e( 'Stored only in the visitor\'s browser and cleared when the tab is closed. Nothing is sent to or stored on the server.', 'utm-parameter-forwarder' ); ?></p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p class="description">
				<?php
				printf(
					/* translators: %s: plugin version. */
					esc_html__( 'Version %s. To exclude a single link, give it the CSS class "utmpf-ignore".', 'utm-parameter-forwarder' ),
					esc_html( UTMPF_VERSION )
				);
				?>
			</p>
		</div>
		<?php
	}
}
