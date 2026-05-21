<?php
/**
 * Bootstrap for unit tests.
 *
 * @package TruspilotReview
 */

// Stub WordPress functions used by the plugin.
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $key ) {
		if ( '' === $key ) {
			return '';
		}

		return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', $key ) );
	}
}

if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		if ( is_string( $value ) ) {
			return stripslashes( $value );
		}

		if ( is_array( $value ) ) {
			return array_map( 'wp_unslash', $value );
		}

		return $value;
	}
}

if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}

if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $url ) {
		return $url;
	}
}

if ( ! function_exists( 'wp_parse_url' ) ) {
	function wp_parse_url( $url ) {
		return parse_url( $url );
	}
}

if ( ! function_exists( 'esc_textarea' ) ) {
	function esc_textarea( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8' );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return trim( wp_strip_all_tags( $str ) );
	}
}

if ( ! function_exists( 'sanitize_textarea_field' ) ) {
	function sanitize_textarea_field( $str ) {
		return trim( wp_strip_all_tags( $str ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	function wp_strip_all_tags( $string, $remove_breaks = false ) {
		$string = preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', $string );
		$string = strip_tags( $string );

		if ( $remove_breaks ) {
			$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
		}

		return trim( $string );
	}
}

define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'WPINC', 'wp-includes' );
define( 'TRUSPILOT_REVIEW_VERSION', '1.0.0' );
define( 'TRUSPILOT_REVIEW_DIR', dirname( __DIR__ ) . '/' );
define( 'TRUSPILOT_REVIEW_URL', 'https://example.com/wp-content/plugins/truspilot-review/' );

/**
 * Stub WordPress core functions.
 */

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		// no-op for tests
	}
}

if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
		// no-op for tests
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		return $default;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		return true;
	}
}

if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
		return false;
	}
}

if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		return true;
	}
}

require_once dirname( __DIR__ ) . '/includes/class-truspilot-review-parser.php';
require_once dirname( __DIR__ ) . '/includes/class-truspilot-review-scraper.php';
require_once dirname( __DIR__ ) . '/includes/class-truspilot-review-importer.php';
require_once dirname( __DIR__ ) . '/includes/class-truspilot-review-plugin.php';
