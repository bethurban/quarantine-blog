<?php


class _ {

	/**
	 * Our attempt to port useful lodash (https://lodash.com) style functions
	 * to PHP.
	 *
	 * In particular, makes getting / finding info in objects and arrays far simpler.
	 */

	/**
	 * Magic method to call the desired function.
	 * Allows DRY by permitting common checks to be performed once, rather than in each separate method.
	 *
	 * @param string $method
	 * @param array  $parameters
	 *
	 * @return array|mixed|null
	 */
	public static function __callStatic( $method, $parameters ) {

		$has_path = TRUE;

		switch ( $method ) {
			// Collection methods.
			// Some don't accept a path.  Those must be grouped above $has_path = FALSE
			case "find":
			case "first":
			case "last":
				$has_path = FALSE;
			// break intentionally omitted.
			case "get":
			case "set":
			case "has":
				return self::_collection( $method, $parameters, $has_path );
				break;
			case "compact":
			case "flatten":
				return self::_array( $method, $parameters );
				break;
			default:
				trigger_error( 'Method ' . $method . ' does not have a case assigned in class _', E_USER_ERROR );
		}
	}

	/**
	 * Wrapper around all "collection" style calls.
	 * Does the general / common checks to defend against incorrect variable types, ensuring path is parsed, etc.
	 *
	 * @param string $method
	 * @param mixed  $parameters
	 * @param bool   $has_path
	 *
	 * @return array|mixed|null
	 */
	private static function _collection( $method, $parameters, $has_path = TRUE ) {
		$collection = self::get( $parameters, 0 );

		if ( ! self::_is_array_or_object( $collection ) ) {
			return self::get( $parameters, 2, FALSE );
		}

		if ( $has_path ) {
			$parameters = self::set( $parameters, 1, self::_parse_path( self::get( $parameters, [ 1 ] ) ) );
		}

		return self::_call_method( $method, $parameters );
	}

	/**
	 * Wrapper around all "array" style calls.
	 * Does the general / common checks to defend against incorrect variable types, ensuring path is parsed, etc.
	 *
	 * @param string $method
	 * @param mixed  $parameters
	 *
	 * @return array|mixed|null
	 */
	private static function _array( $method, $parameters ) {
		$array = self::get( $parameters, 0 );

		if ( ! self::_is_array_or_object( $array ) ) {
			return $array;
		}

		return self::_call_method( $method, $parameters );
	}

	/**
	 * Wrapper to call the method.
	 * Defensively checks that the method exists before attempting to call it.
	 *
	 * @param string $method
	 * @param mixed  $parameters
	 *
	 * @return mixed
	 */
	private static function _call_method( $method, $parameters ) {
		if ( method_exists( __CLASS__, $method ) ) {
			return call_user_func_array( [ __CLASS__, $method ], $parameters );
		} else {
			trigger_error( 'Method ' . substr( $method, 1 ) . ' does not exist in class _', E_USER_ERROR );
		}
	}

	/**
	 * Lodash-style get: https://lodash.com/docs/4.16.4#get
	 * IMPORTANT NOTE: Does NOT support mixed dot and brace path notation, such as 'a[0].b.c'
	 *
	 * @param array|object $collection
	 * @param string|array $path - path to value as an array, or dot-notation separated (eg: '0.key.sub-key')
	 *
	 * @param mixed        $default
	 *
	 * @return array|mixed|null
	 */
	private static function get( $collection, $path, $default = NULL ) {
		foreach ( (array) $path AS $key ) {
			if ( ! self::_is_valid_key( $key ) ) {
				$collection = $default;
				break;
			}

			// Cast to an array in case object / mixed
			$collection = (array) $collection;

			// If the key we're after is not set, then exit and return default
			// NOTE: isset says "FALSE" even if set but NULL, so use array_key_exists instead
			if ( ! array_key_exists( $key, $collection ) ) {
				$collection = $default;
				break;
			}

			// Get the target value from the array
			$collection = $collection[ $key ];
		}

		return $collection;
	}

	/**
	 * Lodash-style has: https://lodash.com/docs/4.16.4#has
	 * IMPORTANT NOTE: Does NOT support mixed dot and brace path notation, such as 'a[0].b.c'
	 *
	 * @param array|object $collection
	 * @param string|array $path
	 *
	 * @return bool
	 */
	private static function has( $collection, $path ) {
		$check = self::_gen_uuid();

		return ( $check !== self::get( $collection, $path, $check ) );
	}

	/**
	 * WIP.  UNTESTED.
	 *
	 * @param $collection
	 * @param $path
	 * @param $value
	 *
	 * @return array|object
	 */
	private static function set( $collection, $path, $value ) {
		$new_path = [];
		foreach ( (array) $path AS $key ) {
			if ( ! self::_is_valid_key( $key ) ) {
				return $collection;
			}

			$new_path[] = $key;
			if ( ! self::has( $collection, $new_path ) ) {
				$assign     = ( $new_path == $path ) ? $value : [];
				$collection = self::_add_path( $collection, $key, $assign );
			} else if ( $new_path == $path ) {
				$collection = self::_add_path( $collection, $key, $value );
			}
		}

		return $collection;
	}


	/**
	 * Lodash-style find: https://lodash.com/docs/4.17.2#find
	 * IMPORTANT NOTE: Does NOT support _.matches style predicates.
	 *
	 * @param array|object $collection
	 * @param array        $predicate - associative array of properties => values, eg. ['key1' => true, 'key2' => 6]
	 *
	 * @return mixed|null
	 */
	private static function find( $collection, $predicate ) {
		// If the passed in variable is not an array or object, then return the default value
		if ( ! self::_is_array_or_object( $collection ) ) {
			return NULL;
		}

		if ( is_object( $predicate ) ) {
			$predicate = (array) $predicate;
		}

		if ( ! is_array( $predicate ) ) {
			return NULL;
		}

		foreach ( (array) $collection AS $row ) {
			$match = TRUE;
			foreach ( $predicate AS $key => $value ) {
				if ( $value !== self::get( $row, $key ) ) {
					$match = FALSE;
					break;
				}
			}

			if ( $match ) {
				return $row;
			}
		}

		return FALSE;
	}

	/**
	 * WIP. UNTESTED.
	 *
	 * @param array|object $collection
	 * @param array|string $path
	 *
	 * @return FALSE|array|object
	 *
	 * @deprecated
	 * Adapted from bottomline: https://github.com/maciejczyzewski/bottomline/blob/master/src/__/collections/filter.php
	 * Returns an array of values belonging to a given property of each item in a collection.
	 *
	 */
	private static function pluck( $collection, $path ) {
		$plucked = array_map( function ( $value ) use ( $path ) {
			return self::get( $value, $path );
		}, (array) $collection );

		if ( is_object( $collection ) ) {
			$plucked = (object) $plucked;
		}

		return $plucked;
	}

	/**
	 * WIP. UNTESTED.
	 *
	 * @param array|object $collection
	 * @param int          $num
	 *
	 * @return array|bool|object
	 * @deprecated
	 * Adapted from bottomline: https://github.com/maciejczyzewski/bottomline/blob/master/src/__/collections/first.php
	 *
	 */
	private static function first( $collection, $num = 1 ) {
		if ( ! (int) $num ) {
			return FALSE;
		}

		$first = array_slice( (array) $collection, 0, $num, TRUE );

		if ( is_object( $collection ) ) {
			$first = (object) $first;
		}

		return $first;
	}

	/**
	 * WIP. UNTESTED.
	 *
	 * @param array|object $collection
	 * @param int          $num
	 *
	 * @return array|bool|object
	 * @deprecated
	 * Adapted from bottomline: https://github.com/maciejczyzewski/bottomline/blob/master/src/__/collections/first.php
	 *
	 */
	private static function last( $collection, $num = 1 ) {
		$last = array_slice( (array) $collection, ( -1 * $num ), NULL, TRUE );

		if ( is_object( $collection ) ) {
			$last = (object) $last;
		}

		return $last;
	}

	/**
	 * WIP. UNTESTED.
	 *
	 * @param array|object $array
	 *
	 * @return array|object
	 * @deprecated
	 *
	 */
	private static function compact( $array ) {
		$is_object = ( ! is_object( $array ) );
		$array     = array_values( array_filter( (array) $array ) );

		if ( $is_object ) {
			$array = (object) $array;
		}

		return $array;
	}

	/**
	 * Flattens a multidimensional array. If you pass shallow, the array will only be flattened a single level.
	 *
	 * Adapted form bottomline: https://github.com/maciejczyzewski/bottomline/blob/master/src/__/arrays/flatten.php
	 *
	 * @param array $array
	 * @param bool  $shallow
	 *
	 * @return array
	 *
	 */
	private static function flatten( $array, $shallow = FALSE ) {
		return self::flatten_recursive( $array, $shallow, FALSE );
	}

	/**
	 * Utility to determine if a variable is an object or array
	 *
	 * @param mixed $collection
	 *
	 * @return bool
	 */
	private static function _is_array_or_object( $collection ) {
		return ( is_array( $collection ) || is_object( $collection ) );
	}

	/**
	 * Utility to test that the array / object key is valid.
	 *
	 * @param mixed $key
	 *
	 * @return bool
	 */
	private static function _is_valid_key( $key ) {
		return ( is_scalar( $key ) && NULL !== $key );
	}

	/**
	 * Utility to add a key / property to an object / array.
	 *
	 * @param array|object $collection
	 * @param string       $key
	 * @param mixed        $value
	 *
	 * @return array|object
	 */
	private static function _add_path( $collection, $key, $value = [] ) {
		if ( is_array( $collection ) ) {
			$collection[ $key ] = $value;
		}

		if ( is_object( $collection ) ) {
			if ( is_array( $value ) ) {
				$value = (object) $value;
			}

			$collection->{$key} = $value;
		}

		return $collection;
	}

	/**
	 * Utility to parse the accepted paths into an array.
	 *
	 * @param mixed $path
	 *
	 * @return array
	 */
	private static function _parse_path( $path ) {
		// Parse the path if it is passed in as a string
		if ( ! is_array( $path ) ) {
			// Explode if dot-notation
			if ( FALSE !== stripos( $path, '.' ) ) {
				$path = explode( '.', $path );
			} else {
				// Otherwise may be a single key, cast to an array
				$path = (array) $path;
			}
		}

		return $path;
	}

	private static function _gen_uuid() {
		return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x', // 32 bits for "time_low"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

			// 16 bits for "time_mid"
			mt_rand( 0, 0xffff ),

			// 16 bits for "time_hi_and_version",
			// four most significant bits holds version number 4
			mt_rand( 0, 0x0fff ) | 0x4000,

			// 16 bits, 8 bits for "clk_seq_hi_res",
			// 8 bits for "clk_seq_low",
			// two most significant bits holds zero and one for variant DCE1.1
			mt_rand( 0, 0x3fff ) | 0x8000,

			// 48 bits for "node"
			mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ) );
	}

	/**
	 * flatten recursive function.
	 *
	 * @param array $array
	 * @param bool  $shallow
	 * @param bool  $strict
	 *
	 * @return array
	 *
	 */
	private static function flatten_recursive( array $array, $shallow = FALSE, $strict = TRUE ) {
		$output = [];
		$idx    = 0;
		foreach ( $array as $index => $value ) {
			if ( is_array( $value ) ) {
				if ( ! $shallow ) {
					$value = self::flatten_recursive( $value, $shallow, $strict );
				}
				$j   = 0;
				$len = count( $value );
				while ( $j < $len ) {
					$output[ $idx++ ] = $value[ $j++ ];
				}
			} elseif ( ! $strict ) {
				$output[ $idx++ ] = $value;
			}
		}

		return $output;
	}
}
