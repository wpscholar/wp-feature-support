<?php

namespace wpscholar\WordPress;

/**
 * Class FeatureSupport
 *
 * @package wpscholar\WordPress
 */
class FeatureSupport {

	/**
	 * A collection of FeatureSupport instances.
	 *
	 * @var array
	 */
	protected static $instances = [];

	/**
	 * A collection of features.
	 *
	 * @var array
	 */
	protected $features = [];

	/**
	 * Get an instance of FeatureSupport for a specific object type (e.g. taxonomy, site, network, etc.)
	 *
	 * @param string $type
	 *
	 * @return FeatureSupport
	 */
	public static function getInstance( $type ) {
		if ( ! array_key_exists( $type, self::$instances ) ) {
			self::$instances[ $type ] = new self();
		}

		return self::$instances[ $type ];
	}

	/**
	 * Get a list of all registered object types.
	 *
	 * @return array
	 */
	public static function getRegisteredTypes() {
		return array_keys( self::$instances );
	}

	/**
	 * Prevent external instantiation.
	 */
	protected function __construct() {
	}

	/**
	 * Prevent cloning.
	 */
	protected function __clone() {
	}

	/**
	 * Prevent unserialization.
	 */
	protected function __wakeup() {
	}

	/**
	 * Check if an item has support for a specific feature.
	 *
	 * @param string $item Item name.
	 * @param string $feature Feature name.
	 *
	 * @return bool
	 */
	public function has( $item, $feature ) {
		return isset( $this->features[ $item ], $this->features[ $item ][ $feature ] );
	}

	/**
	 * Add support for a feature to an item.
	 *
	 * @param string $item
	 * @param string|array $feature
	 */
	public function add( $item, $feature ) {
		$features = (array) $feature;
		foreach ( $features as $feature ) {
			if ( func_num_args() == 2 ) {
				$this->features[ $item ][ $feature ] = true;
			} else {
				$this->features[ $item ][ $feature ] = array_slice( func_get_args(), 2 );
			}
		}
	}

	/**
	 * Remove support for a feature from an item.
	 *
	 * @param string $item
	 * @param string $feature
	 */
	public function remove( $item, $feature ) {
		unset( $this->features[ $item ][ $feature ] );
	}

	/**
	 * Get the value of a feature that an item supports.
	 *
	 * @param string $item
	 * @param string $feature
	 *
	 * @return mixed
	 */
	public function get( $item, $feature ) {
		return $this->has( $item, $feature ) ? $this->features[ $item ][ $feature ] : null;
	}

	/**
	 * Get all of the features that an item supports.
	 *
	 * @param string $item Item name.
	 *
	 * @return array Item supports list.
	 */
	public function all( $item ) {
		return isset( $this->features[ $item ] ) ? $this->features[ $item ] : [];
	}

	/**
	 * Find all items that where one or more features are supported.
	 *
	 * @param string|array $feature
	 * @param string $operator
	 *
	 * @return array|\WP_Error
	 */
	public function where( $feature, $operator = 'AND' ) {

		if ( ! is_string( $feature ) && ! is_array( $feature ) ) {
			return new \WP_Error( 'invalid_argument', __( 'Invalid argument.' ) );
		}

		if ( is_string( $feature ) || ( is_array( $feature ) && is_numeric( key( $feature ) ) ) ) {

			// Create a collection of item names indexed by feature name.
			$collection = [];
			foreach ( $this->features as $item => $supports ) {
				foreach ( $supports as $feature_name => $value ) {
					$collection[ $feature_name ][] = $item;
				}
			}

			if ( is_string( $feature ) ) {

				switch ( strtoupper( $operator ) ) {
					case 'NOT':
						// Return all items that don't support the provided feature.
						return array_values( array_diff( $this->keys(), $collection[ $feature ] ) );
					default:
						// The AND and OR operators are irrelevant. Return all items that support the provided feature.
						return isset( $collection[ $feature ] ) ? $collection[ $feature ] : [];
				}

			} else {

				// If array is numerically indexed, find items that support the provided features based on the operator.

				$matches = [];
				foreach ( $feature as $feature_name ) {
					$matches[] = $collection[ $feature_name ];
				}

				switch ( strtoupper( $operator ) ) {
					case 'OR':
						return array_values( array_unique( array_merge( ...$matches ) ) );
					case 'NOT';
						return array_values( array_diff( $this->keys(), array_merge( ...$matches ) ) );
					default:
						return array_values( array_intersect( ...$matches ) );
				}

			}

		}

		// If array is associative, filter by key as feature name and value as feature value.
		return array_keys( wp_filter_object_list( $this->features, $feature, $operator ) );

	}

	/**
	 * Get a list of keys for all registered items.
	 *
	 * @return array
	 */
	public function keys() {
		return array_keys( $this->features );
	}

}