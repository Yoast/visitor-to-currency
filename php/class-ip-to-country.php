<?php

namespace Yoast\YoastCom\VisitorCurrency;

use Yoast\YoastCom\Api\Transient_Cache;

class IP_To_Country implements Lookup_Interface {
	/**
	 * Lookup IP to country
	 *
	 * @param string $IP IP to lookup
	 *
	 * @return string|null
	 */
	public function lookup( $IP ) {

		$url = sprintf( 'https://freegeoip.net/json/%1$s', $IP );

		$data = $this->get_cached_response( $url );
		if ( empty( $data ) ) {
			$response = $this->get_request_response( $url );

			if ( empty( $response ) || is_wp_error( $response ) ) {
				return null;
			}

			$data = $response['body'];
		}

		$decoded = json_decode( $data );

		return $decoded->country_code;
	}

	/**
	 * Get the cached response
	 *
	 * @param string $url URL to get response of
	 *
	 * @return mixed
	 */
	protected function get_cached_response( $url ) {

		$cache = new Transient_Cache( 'ip2country' );

		return $cache->get( $url );
	}

	/**
	 * Get the request response data
	 *
	 * @param string $url URL to fetch data from
	 *
	 * @return array|\WP_Error
	 */
	protected function get_request_response( $url ) {
		$response = wp_remote_request( $url );

		$data = '';
		if ( is_array( $response ) ) {
			$data = $response['body'];
		}

		$cache = new Transient_Cache( 'ip2country' );
		$cache->set( $url, $data );

		return $response;
	}
}
