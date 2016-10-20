<?php

namespace Yoast\YoastCom\VisitorCurrency;

class Currency_Controller {

	protected $default_currency;
	protected $currency;
	protected $currencies = array();

	protected $currency_cookie;

	protected static $instance;

	protected $language_to_country;
	protected $ip_to_country;
	protected $country_to_currency;

	/**
	 * @return Currency_Controller
	 */
	public static function get_instance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new self( new IP_To_Country(), new Country_To_Currency(), new Language_To_Country() );
			self::$instance->set_currency_cookie_name( 'yoast_cart_currency' );
			self::$instance->add_currency( 'USD', true );
			self::$instance->add_currency( 'EUR' );
		}

		return self::$instance;
	}

	/**
	 * @param      $currency
	 * @param bool $default
	 */
	public function add_currency( $currency, $default = false ) {
		if ( $default ) {
			array_unshift( $this->currencies, $currency );
			$this->default_currency = $currency;
		} else {
			$this->currencies[] = $currency;
		}

		$this->currencies = array_unique( $this->currencies );
	}

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
	}

	/**
	 * @param null|string $force_currency
	 *
	 * @return string
	 */
	public function get_currency( $force_currency = null ) {
		if ( ! is_null( $force_currency ) && $this->is_valid_currency( $force_currency ) ) {
			return $force_currency;
		}

		if ( ! isset( $this->currency ) ) {
			$this->currency = $this->detect_currency();
		}

		return $this->currency;
	}

	/**
	 * Get the default currency
	 *
	 * @return string
	 */
	public function get_default_currency() {
		return $this->default_currency;
	}

	/**
	 * @param $currency
	 */
	public function set_currency( $currency ) {
		if ( ! $this->is_valid_currency( $currency ) ) {
			throw new \InvalidArgumentException( 'Invalid currency supplied: ' . $currency );
		}

		$this->currency = $currency;
		$this->set_currency_cookie( $currency );
	}

	/**
	 * @return array
	 */
	public function get_currencies() {
		// If a currency is forced, only return this.
		$forced = apply_filters( 'yoast_detect_visitor_currency', null );
		if ( $forced ) {
			return array( $forced );
		}

		return $this->currencies;
	}

	/**
	 * @return bool|null|string
	 */
	public function detect_currency() {

		$forced = apply_filters( 'yoast_detect_visitor_currency', null );
		if ( ! is_null( $forced ) ) {
			return $forced;
		}

		$currency = $this->get_currency_from_cookie();
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

		return $this->default_currency;
	}

	/**
	 * @return string|bool
	 */
	private function get_currency_from_cookie() {
		if ( isset( $_COOKIE[ $this->get_currency_cookie_name() ] ) ) {
			return $_COOKIE[ $this->get_currency_cookie_name() ];
		}

		return false;
	}

	/**
	 * @param $currency
	 */
	private function set_currency_cookie( $currency ) {
		setcookie( $this->get_currency_cookie_name(), $currency, YEAR_IN_SECONDS, '/', '.yoast.com' );
	}

	/**
	 * @return null
	 */
	private function get_currency_from_IP() {
		$IP = isset( $_SERVER['HTTP_X_SUCURI_CLIENTIP'] ) ? $_SERVER['HTTP_X_SUCURI_CLIENTIP'] : $_SERVER['REMOTE_ADDR'];

		$country = $this->ip_to_country->lookup( $IP );
		if ( is_null( $country ) ) {
			return null;
		}

		return $this->country_to_currency->lookup( $country );
	}

	/**
	 * @param string $amount Amount to format
	 * @param null   $use_currency
	 *
	 * @return string
	 */
	public function format_price( $amount, $use_currency = null ) {
		if ( preg_match( '/\.00$/', $amount ) ) {
			$amount = str_replace( '.00', '', $amount );
		}

		return sprintf( '%s %s', $this->get_currency_display( $use_currency ), $amount );
	}

	/**
	 * @param null $use_currency
	 *
	 * @return string
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
	 * @return null
	 */
	protected function get_currency_from_headers() {

		$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$country         = $this->language_to_country->lookup( $accept_language );
		if ( is_null( $country ) ) {
			return null;
		}

		return $this->country_to_currency->lookup( $country );
	}

	/**
	 * @return mixed
	 */
	protected function get_currency_cookie_name() {
		return $this->currency_cookie;
	}

	/**
	 * @param $currency_cookie_name
	 */
	protected function set_currency_cookie_name( $currency_cookie_name ) {
		$this->currency_cookie = $currency_cookie_name;
	}

	/**
	 * @param $currency
	 *
	 * @return bool
	 */
	protected function is_valid_currency( $currency ) {
		// If a currency is forced, only return this.
		$forced = apply_filters( 'yoast_detect_visitor_currency', null );
		if ( $forced ) {
			return $forced == $currency;
		}

		return in_array( $currency, $this->currencies );
	}
}
