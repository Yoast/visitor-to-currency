<?php namespace Yoast\YoastCom\VisitorCurrency;

/**
 * Class Vat_Lookup
 * @package Yoast\YoastCom\VisitorCurrency
 */
class Vat_Lookup {

	/**
	 * Retrieves the VAT rules that apply within the EU.
	 *
	 * @param bool $force_update
	 *
	 * @return mixed|void
	 */
	public function get_euro_vat_rules( $force_update = false ) {
		$option = get_option( 'yst_vat_euro' );

		if ( $option !== false && ! $force_update && ! $this->vat_rules_have_expired() ) {
			return $option;
		}

		$request = $this->lookup_euro_vat_rules();

		// If we didn't get a proper response back, use the already existing option value.
		if ( empty( $request ) ) {
			return $option;
		}

		update_option( 'yst_vat_euro', [
			'rules' => $request->rates,
			'updated_at' => new \DateTime()
		] );

		return get_option( 'yst_vat_euro' );
	}

	public function get_applicable_countries_in_eu() {
		$rates = $this->get_euro_vat_rules();

		$countries = [];

		foreach ( $rates['rules'] as $eu_rate ) {
			$countries[] = $eu_rate->code;
		}

		return $countries;
	}

	/**
	 * Determines whether the current VAT rules have expired and thus should be refreshed.
	 *
	 * @return bool
	 */
	private function vat_rules_have_expired() {
		$option = get_option( 'yst_vat_euro' );

		if ( $option === false ) {
			return true;
		}

		$updated_at = $option['updated_at'];

		return $updated_at->diff( new \DateTime() )->days >= 1;
	}

	/**
	 * Executes an API call to retrieve the VAT rules that are applicable in the EU.
	 *
	 * @return array|mixed|object
	 */
	private function lookup_euro_vat_rules() {
		$result = [];

		$response = wp_remote_get( 'http://jsonvat.com/' );

		if ( is_array( $response ) && $response['response']['code'] === 200 ) {
			$result = json_decode( $response['body'] );
		}

		return $result;
	}
}
