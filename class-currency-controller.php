<?php

namespace Yoast\YoastCom\VisitorCurrency;

class Currency_Controller {

	protected $default_currency = 'USD';
	protected $currency;

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
		}

		return self::$instance;
	}

	/**
	 * Currency_Controller constructor.
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
	 * @return bool|null|string
	 */
	public function get_currency() {
		if ( ! isset( $this->currency ) ) {
			$this->currency = $this->detect_currency();
		}

		return $this->currency;
	}

	public function set_currency( $currency ) {
		$this->currency = $currency;
		$this->set_currency_cookie( $currency );
	}

	/**
	 * @return bool|null|string
	 */
	public function detect_currency() {
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
		$country = $this->ip_to_country->lookup( $_SERVER['REMOTE_ADDR'] );
		if ( is_null( $country ) ) {
			return null;
		}

		return $this->country_to_currency->lookup( $country );
	}

	/**
	 * @param string $amount Amount to format
	 *
	 * @return string
	 */
	public function format_price( $amount ) {
		if ( preg_match( '/\.00$/', $amount ) ) {
			$amount = str_replace( '.00', '', $amount );
		}

		return sprintf( '%s %s', $this->get_currency_display(), $amount );
	}

	/**
	 * @return string
	 */
	public function get_currency_display() {
		switch ( $this->get_currency() ) {
			case 'EUR':
				return '&euro;';
		}

		return '$';
	}

	/**
	 * @return null
	 */
	private function get_currency_from_headers() {

		$accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		$country         = $this->language_to_country->lookup( $accept_language );
		if ( is_null( $country ) ) {
			return null;
		}

		return $this->country_to_currency->lookup( $country );
	}

	private function get_currency_cookie_name() {
		return $this->currency_cookie;
	}

	private function set_currency_cookie_name( $currency_cookie_name ) {
		$this->currency_cookie = $currency_cookie_name;
	}
}
