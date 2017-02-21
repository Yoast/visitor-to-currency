<?php

namespace Yoast\YoastCom\VisitorCurrency;

class Currency_Controller {

	protected $default_currency;
	protected $currency;
	protected $currencies = [];

	protected $currency_cookie;

	protected static $instance;

	protected $language_to_country;
	protected $ip_to_country;
	protected $country_to_currency;

	protected $supported_currencies = [];

	/**
	 * Currency_Controller constructor.
	 *
	 * @todo SRP: re-structure to add a lookup with a priority
	 *
	 * @param Lookup_Interface $ip_to_country
	 * @param Lookup_Interface $country_to_currency
	 * @param Lookup_Interface $language_to_country
	 */
	public function __construct( Lookup_Interface $ip_to_country, Lookup_Interface $country_to_currency, Lookup_Interface $language_to_country ) {
		$this->ip_to_country       = $ip_to_country;
		$this->country_to_currency = $country_to_currency;
		$this->language_to_country = $language_to_country;

		$this->currency_manager = new Currency_Manager();
	}

	/**
	 * Gets the instance of the Currency Controller.
	 *
	 * @return Currency_Controller The Currency Controller class.
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( new IP_To_Country(), new Country_To_Currency(), new Language_To_Country() );

			self::$instance->set_currency_cookie_name( 'yoast_cart_currency' );
			self::$instance->add_supported_currencies();

//			if ( self::$instance->get_currency_from_cookie() === false ) {
//				self::$instance->set_currency_cookie( self::$instance->currency_manager->get_default_currency()->get_code() );
//			}
		}

		return self::$instance;
	}

	/**
	 * Adds a currency to the list of possibilities.
	 *
	 * @param string $currency The currency to add.
	 * @param string $label The label to give the currency.
	 * @param bool   $default  Whether or not the currency should be set as an default.
	 *
	 * @return void
	 */
	public function add_currency( $currency, $label, $default = false ) {
		// Don't add a currency if it already exists in the list.
		if ( array_key_exists( $currency, $this->currencies ) ) {
			return;
		}

		if ( $default ) {
			$this->set_default_currency( $currency );
			return;
		}

		$this->currencies[ $currency ] = $label;

		$this->sanitize_currencies();
	}

	/**
	 * Ensures that there are no duplicate currencies.
	 *
	 * @return void
	 */
	private function sanitize_currencies() {
		$this->currencies = array_unique( $this->currencies );
	}

	/**
	 * Adds a currency and sets it as the default currency.
	 *
	 * @param string $currency The currency to set as a default.
	 * @return void
	 */
	protected function add_default_currency( $currency ) {
		array_unshift( $this->currencies, $currency );
		$this->set_default_currency( $currency );
		$this->sanitize_currencies();
	}

	/**
	 * Sets the default currency.
	 *
	 * @param string $currency The currency to set as a default.
	 * @return void
	 */
	protected function set_default_currency( $currency ) {
		$this->default_currency = $currency;
	}

	/**
	 * Gets the currency.
	 *
	 * @param null|string $force_currency The currency to enforce.
	 *
	 * @return string The detected currency.
	 * @throws \Exception
	 * @throws \InvalidArgumentException
	 */
	public function get_currency( $force_currency = null ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return $this->default_currency;
		}

		if ( ! is_null( $force_currency ) && $this->is_valid_currency( $force_currency ) ) {
			return $force_currency;
		}

		if ( $this->currency_manager->has_current_currency() ) {
			return $this->currency_manager->get_current_currency()->get_code();
		}

		$detected = $this->detect_currency();

		if ( is_string( $detected ) ) {
			$detected = $this->currency_manager->get_by_code( $detected );
		}

		if ( $detected === null ) {
			$detected = $this->currency_manager->get_default_currency();
		}

		$this->currency_manager->set_current_currency( $detected );

		return $detected;
	}

	/**
	 * Sets the currency by first attempting to detect it. Uses the default if none can be detected.
	 *
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function set_currency_by_detection() {
		$detected = $this->detect_currency();

		if ( $detected === null ) {
			$detected = $this->currency_manager->get_default_currency();
		}

		$this->set_currency( $detected );
	}

	/**
	 * Gets the default currency.
	 *
	 * @return string The default currency.
	 */
	public function get_default_currency() {
		return $this->currency_manager->get_default_currency();
	}

	/**
	 * Sets the current currency and add a cookie to store it.
	 *
	 * @param string $currency The currency to set.
	 * @throws \InvalidArgumentException
	 *
	 * @return void
	 */
	public function set_currency( $currency ) {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			return;
		}

		if ( ! $this->is_valid_currency( $currency ) ) {
			throw new \InvalidArgumentException( 'Invalid currency supplied: ' . $currency );
		}

		$this->currency = $currency;
		$this->set_currency_cookie( $currency );
	}

	/**
	 * Gets a list of currencies.
	 *
	 * @return array The list of available currencies.
	 */
	public function get_currencies() {
		// If a currency is forced, only return this.
		$forced = $this->get_forced_currency();

		if ( $forced ) {
			return array( $forced );
		}

		return $this->currency_manager->toArray();
	}

	/**
	 * Gets the enforced currency (if applicable).
	 *
	 * @return string|void The enforced currency.
	 */
	private function get_forced_currency() {
		return apply_filters( 'yoast_detect_visitor_currency', null );
	}

	public function get_by_country_code( $code ) {
		$currency = $this->country_to_currency->lookup( $code );

		if ( $currency !== null ) {
			$this->currency_manager->disable_except( $currency );
		}

		return $this->currency_manager->toArray();
	}

	/**
	 * Detects the currency via multiple methods.
	 *
	 * @return bool|null|string The detected currency. Defaults to null.
	 * @throws \InvalidArgumentException
	 */
	public function detect_currency() {
		$forced = $this->get_forced_currency();

		if ( ! is_null( $forced ) ) {
			return $forced;
		}

		$currency = $this->get_currency_from_cart();

		if ( $currency ) {
			return $currency;
		}

		$currency = $this->get_currency_from_IP();

		if ( $currency ) {
			$this->set_currency( $currency );

			return $currency;
		}

		$currency = $this->get_currency_from_headers();

		if ( $currency ) {
			$this->set_currency( $currency );

			return $currency;
		}

		return null;
	}

	/**
	 * Retrieves a list of all countries that are in the European Union.
	 *
	 * @return array The list of EU countries.
	 */
	public function get_eu_countries() {
		$vat_lookup = new Vat_Lookup();
		return $vat_lookup->get_applicable_countries_in_eu();
	}

	/**
	 * Gets the currency from the cookie.
	 *
	 * @return string|bool Gets the currency from the cookie. Returns false if the cookie can't be found.
	 */
	public function get_currency_from_cookie() {
		return $this->get_cookie( $this->get_currency_cookie_name() );
	}

	/**
	 * Gets a cookie by a specific name.
	 *
	 * @param string $cookie The cookie to retrieve.
	 * @return bool|string The cookie (if found). Defaults to false.
	 */
	public function get_cookie( $cookie ) {
		if ( isset( $_COOKIE[ $cookie ] ) ) {
			return $_COOKIE[ $cookie ];
		}

		return false;
	}

	/**
	 * Adds a cookie for both the dev and live environments.
	 *
	 * @param string $name The name of the cookie.
	 * @param string $value The value of the cookie.
	 * @return void
	 */
	private function add_cookie( $name, $value ) {
		setcookie( $name, $value, $_SERVER['REQUEST_TIME'] + YEAR_IN_SECONDS, '/', '.yoast.dev' );
		setcookie( $name, $value, $_SERVER['REQUEST_TIME'] + YEAR_IN_SECONDS, '/', '.yoast.com' );
	}

	/**
	 * Sets the currency cookie.
	 *
	 * @param $currency The currency to set.
	 * @return void
	 */
	public function set_currency_cookie( $currency ) {
		$cookie_name = $this->get_currency_cookie_name();

		if ( isset( $_COOKIE[ $cookie_name ] ) && $_COOKIE[ $cookie_name ] == $currency ) {
			return;
		}

		$this->add_cookie( $cookie_name, $currency );
	}

	/**
	 * Detects the currency based on IP address.
	 *
	 * @return null|string The detected currency. Defaults to null.
	 */
	private function get_currency_from_IP() {
		$IP = isset( $_SERVER['HTTP_X_SUCURI_CLIENTIP'] ) ? $_SERVER['HTTP_X_SUCURI_CLIENTIP'] : $_SERVER['REMOTE_ADDR'];

		$country = $this->ip_to_country->lookup( $IP );

		if ( is_null( $country ) ) {
			return null;
		}

		$currency = $this->country_to_currency->lookup( $country );

		return $currency;
	}

	/**
	 * Formats the prices according to the countries' standards.
	 *
	 * @param string $amount Amount to format.
	 * @param null   $use_currency The currency to force.
	 *
	 * @return string The formatted price.
	 */
	public function format_price( $amount, $use_currency = null ) {
		if ( preg_match( '/\.00$/', $amount ) ) {
			$amount = str_replace( '.00', '', $amount );
		}

		return sprintf( '%s %s', $this->get_currency_display( $use_currency ), $amount );
	}

	/**
	 * Gets the currency symbol or other form of display.
	 *
	 * @param null|string $use_currency The currency to force.
	 *
	 * @return string The currency symbol. Defaults to $.
	 */
	public function get_currency_display( $use_currency = null ) {
		$currency = is_null( $use_currency ) ? $this->get_currency() : $use_currency;

		switch ( $currency ) {
			case 'EUR':
				return '&euro;';
		}

		return '$';
	}

	/**
	 * Detects the currency based on the HTTP headers.
	 *
	 * @return null|string Currency if detected, otherwise null.
	 */
	protected function get_currency_from_headers() {

		$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$country         = $this->language_to_country->lookup( $accept_language );
		$currency        = null;

		if ( ! is_null( $country ) ) {
			$currency = $this->country_to_currency->lookup( $country );
		}

		return $currency;
	}

	/**
	 * Detects currency from selected country in the cart.
	 *
	 * @return null|string Currency if detected, otherwise null.
	 */
	protected function get_currency_from_cart() {
		$currency = $this->country_to_currency->lookup( edd_get_shop_country() );
		$by_address = $this->get_currency_by_address();

		if ( ! empty( $by_address ) ) {
			return $by_address;
		}

		if ( $this->has_currency_cookie() ) {
			return $this->get_currency_from_cookie();
		}

		return $currency;
	}

	/**
	 * Detects the currency based on the billing country.
	 *
	 * @return null|string Currency if detected, otherwise null
	 */
	protected function get_currency_by_address() {
		$billing_country = $this->get_billing_country();

		return $this->country_to_currency->lookup( $billing_country );
	}

	/**
	 * Gets the billing country from either a POST value or from EDD.
	 *
	 * @return string The billing country.
	 */
	protected function get_billing_country() {
		if ( ! empty( $_POST['billing_country'] ) ) {
			return $_POST['billing_country'];
		}

		return '';
	}

	/**
	 * Gets the currency cookie's name.
	 *
	 * @return string The name of the currency cookie.
	 */
	protected function get_currency_cookie_name() {
		return $this->currency_cookie;
	}

	/**
	 * Sets the currency cookie's name.
	 *
	 * @param string $currency_cookie_name The name to set.
	 */
	protected function set_currency_cookie_name( $currency_cookie_name ) {
		$this->currency_cookie = $currency_cookie_name;
	}

	/**
	 * Determines whether or not there's a currency cookie present.
	 *
	 * @return bool Whether or not there's a currency cookie.
	 */
	public function has_currency_cookie() {
		return $this->get_currency_from_cookie() !== false;
	}

	/**
	 * Determines whether or not the currency passed is considered valid.
	 *
	 * @param string $currency The currency to check.
	 *
	 * @return bool Whether or not the currency is considered valid.
	 */
	protected function is_valid_currency( $currency ) {
		// If a currency is forced, only return this.
		$forced = $this->get_forced_currency();

		if ( $forced ) {
			return $forced === $currency;
		}

		return in_array( $currency, $this->currency_manager->get_codes() );
	}

	/**
	 * Adds supported currencies.
	 *
	 * @return void
	 */
	private function add_supported_currencies() {
		$this->currency_manager->add_currency( new Currency( 'EUR', __( 'EUR (&euro;)', 'yoastcom' ) ) );
		$this->currency_manager->add_currency( new Currency( 'USD', __( 'USD ($)', 'yoastcom' ), true, true ) );
	}
}
