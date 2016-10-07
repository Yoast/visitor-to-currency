<?php

namespace Yoast\YoastCom\VisitorCurrency;

class Language_To_Country implements Lookup_Interface {

	protected $lookup = array();

	/**
	 * Complicated, maybe implement later.
	 *
	 * @param $language
	 *
	 * @return null
	 */
	public function lookup( $language ) {

		if ( isset($this->lookup[ $language ] ) ) {
			return $this->lookup[ $language ];
		}

		return null;

	}
}
