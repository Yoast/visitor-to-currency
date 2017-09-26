<?php namespace Yoast\YoastCom\VisitorCurrency;

/**
 * Class Currency_Manager
 * @package Yoast\YoastCom\VisitorCurrency
 */
class Currency_Manager {
	private $currencies = [];
	private $default_currency;
	private $current_currency;

	/**
	 * Adds a currency.
	 *
	 * @param Currency $currency The currency to add.
	 */
	public function add_currency( Currency $currency ) {
		if ( ! $this->is_unique( $currency ) ) {
			return;
		}

		$this->currencies[ $currency->get_code() ] = $currency;

		if ( $currency->is_default() ) {
			$this->default_currency = $currency;
		}
	}

	/**
	 * Removes a currency from the list of supported currencies.
	 *
	 * @param Currency $currency The currency to remove.
	 */
	public function remove_currency( Currency $currency ) {
		if ( ! $this->is_supported( $currency ) ) {
			return;
		}

		unset( $this->currencies[ $currency->get_code() ] );
	}

	/**
	 * Determines whether or not the currency is unique in the list of currencies.
	 *
	 * @param Currency $currency The currency to check for.
	 *
	 * @return bool Whether or not the currency is unique.
	 */
	public function is_unique( Currency $currency ) {
		return ! $this->is_supported( $currency );
	}

	/**
	 * Determines whether or not the currency is supported.
	 *
	 * @param Currency $currency The currency to check for.
	 *
	 * @return bool Whether or not the currency is supported.
	 */
	public function is_supported( Currency $currency ) {
		return array_key_exists( $currency->get_code(), $this->currencies );
	}

	/**
	 * Gets the enabled currencies.
	 *
	 * @return array The list of currencies that are currently enabled.
	 */
	public function get_enabled_currencies() {
		return array_filter( $this->currencies, function( Currency $currency ) {
			return $currency->is_enabled();
		} );
	}

	/**
	 * Gets the codes for the currencies.
	 *
	 * @return array The codes for the currencies.
	 */
	public function get_codes() {
		return array_map( function( $currency ) {
			return $currency->get_code();
		}, $this->currencies );
	}

	/**
	 * Gets all the currencies.
	 *
	 * @return array The currencies.
	 */
	public function get_currencies() {
		return $this->currencies;
	}

	/**
	 * Gets the default currency.
	 *
	 * @return string The default currency.
	 */
	public function get_default_currency() {
		return $this->default_currency;
	}

	/**
	 * Determines whether or not the current currency is set.
	 *
	 * @return bool Whether or not the current currency is set.
	 */
	public function has_current_currency() {
		return $this->current_currency !== null;
	}

	/**
	 * Sets the current currency.
	 *
	 * @param Currency $currency The currency to set as the current one.
	 */
	public function set_current_currency( Currency $currency ) {
		if ( ! $this->is_supported( $currency ) ) {
			return;
		}

		$this->current_currency = $currency;
	}

	/**
	 * Gets the current currency.
	 *
	 * @return mixed
	 */
	public function get_current_currency() {
		return $this->current_currency;
	}

	/**
	 * Retrieves a currency by the country code.
	 *
	 * @param string $code The code to
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function get_by_code( $code ) {
		if ( ! array_key_exists( $code, $this->currencies ) ) {
			throw new \Exception( "Could not retrieve a currency with the code `$code`.");
		}

		return $this->currencies[ $code ];
	}

	/**
	 * Converts the available currencies to an array.
	 *
	 * @param bool $only_enabled Only retrieve currencies that are enabled.
	 *
	 * @return array All the available currencies.
	 */
	public function toArray( $only_enabled = true ) {
		$result = [];

		foreach ( $this->currencies as $currency => $currency_properties ) {

			if ( $only_enabled && ! $currency_properties->is_enabled() ) {
				continue;
			}

			$result[$currency] = $currency_properties->get_label();
		}

		return $result;
	}

	/**
	 * Disables all the other currencies except for the passed one.
	 *
	 * @param $enabled_currency The currency to enable.
	 */
	public function disable_except( $enabled_currency ) {
		foreach ( $this->currencies as $currency ) {
			if ( $currency->get_code() !== $enabled_currency ) {
				$currency->disable();
				$currency->set_default( false );

				continue;
			}

			$currency->enable();
			$currency->set_default();
		}
	}
}
