<?php namespace Yoast\YoastCom\VisitorCurrency;

/**
 * Class Currency
 * @package Yoast\YoastCom\VisitorCurrency
 */
class Currency {
	private $code;
	private $label;
	/**
	 * @var bool
	 */
	private $enabled;
	/**
	 * @var bool
	 */
	private $default;

	/**
	 * Currency constructor.
	 *
	 * @param string $code
	 * @param string $label
	 * @param bool $enabled
	 * @param bool $default
	 */
	public function __construct($code, $label = '', $enabled = true, $default = false)
	{
		$this->code = $code;
		$this->label = $label;
		$this->enabled = $enabled;
		$this->default = $default;
	}

	/**
	 * Gets the code.
	 *
	 * @return string The currency code.
	 */
	public function get_code() {
		return $this->code;
	}

	/**
	 * Gets the label.
	 *
	 * @return string The label.
	 */
	public function get_label() {
		return $this->label;
	}

	/**
	 * Determines whether or not the currency is the default currency.
	 *
	 * @return bool Whether or not the currency is the default currency.
	 */
	public function is_default() {
		return $this->default === true;
	}

	/**
	 * Determines whether or not the currency is enabled.
	 *
	 * @return bool Whether or not the currency is enabled.
	 */
	public function is_enabled() {
		return $this->enabled === true;
	}

	/**
	 * Sets the enabled state of the currency.
	 */
	public function enable() {
		$this->enabled = true;
	}

	/**
	 * Sets the disabled state of the currency.
	 */
	public function disable() {
		$this->enabled = false;
	}

	/**
	 * @param bool $state The default state to set the currency to.
	 */
	public function set_default( $state = true ) {
		$this->default = $state;
	}
}
