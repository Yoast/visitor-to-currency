<?php

namespace Yoast\YoastCom\VisitorCurrency;

class Country_To_Currency implements Lookup_Interface {

	// Source: https://en.wikipedia.org/wiki/Eurozone
	protected $list = [];

	/**
	 * Country_To_Currency constructor.
	 */
	public function __construct()
	{
		$this->add_euro_countries();
		$this->add_dollar_countries();
	}

	/**
	 * Adds countries that have the Euro as their currency.
	 */
	private function add_euro_countries() {
		$this->list['EUR'] = $this->retrieve_euro_countries();
	}

	/**
	 * Adds countries that have the US Dollar as their currency.
	 */
	private function add_dollar_countries() {
		$this->list['USD'] = [ 'US' ];
	}

	/**
	 * Gets the list of countries that have the Euro as their currency.
	 *
	 * @return array List of countries that have the Euro as their default currency.
	 */
	private function retrieve_euro_countries() {
		$countries = [];
		$vat_lookup = new Vat_Lookup();
		$rates = $vat_lookup->get_euro_vat_rules();

		foreach ( $rates['rules'] as $eu_rate ) {
			$countries[] = $eu_rate->code;
		}

		return $countries;
	}

	/**
	 * Converts country to currency
	 *
	 * @param string $country Country to check.
	 *
	 * @return string|null The currency that the country has. Returns null if no specific currency can be detected.
	 */
	public function lookup( $country ) {
		foreach ( $this->list as $currency => $countries ) {
			if ( in_array( $country, $countries, false ) ) {
				return $currency;
			}
		}

		return null;
	}
}
