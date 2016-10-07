<?php

namespace Yoast\YoastCom\VisitorCurrency;

class Country_To_Currency implements Lookup_Interface {

	// Source: https://en.wikipedia.org/wiki/Eurozone
	protected $list = array(
		'EUR' => array(
			'AT',
			'BE',
			'CY',
			'EE',
			'FI',
			'FR',
			'DE',
			'GR',
			'IE',
			'IT',
			'LV',
			'LT',
			'LU',
			'MT',
			'NL',
			'PT',
			'SK',
			'SI',
			'ES',
			'EZ',
		)
	);

	/**
	 * Convert country to currency
	 *
	 * @param string $country Country to check.
	 *
	 * @return string
	 */
	public function lookup( $country ) {
		foreach ( $this->list as $currency => $countries ) {
			if ( isset( $countries[ $country ] ) ) {
				return $currency;
			}
		}

		return 'USD';
	}
}
