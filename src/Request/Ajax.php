<?php

namespace WPML\ElasticPress\Request;

/**
 * Request-policy adapter for this plugin's admin-ajax handlers (wpmldev-7976).
 *
 * Every handler this plugin binds to `wp_ajax_*` / `wp_ajax_nopriv_*` goes
 * through here with a declarative policy spec, never through a raw
 * `add_action()`. With WPML core's request-policy model available the spec
 * becomes a \WPML\Request\Policy\Policy registered through
 * \WPML\Request\Adapter\Ajax, so the core dispatch gate enforces it (and
 * detaches anything bound without one). Against an older core that lacks
 * the model, the handler is registered as before and keeps relying on its
 * own inline checks - the fallback exists so this plugin never fatals on a
 * core it is not paired with, not as a second policy engine.
 *
 * Spec keys (exactly one authorization key, plus one authenticity key):
 *   'capability'    => string|string[]  any-of; administrators always pass
 *   'authorize'     => callable, 'description' => string   (literal true allows)
 *   'authenticated' => string (reason)  any logged-in user, scoped to the caller
 *   'public'        => string (reason)  anyone, incl. anonymous (nopriv registered)
 *   'machine'       => callable, 'description' => string   credential verifier
 *   'nonce'         => [ string $action, string|string[] $field ]
 *   'no_nonce'      => string (reason)
 */
final class Ajax {

	/**
	 * @param string   $action
	 * @param array    $spec
	 * @param callable $callback
	 * @param int      $priority
	 * @param int      $acceptedArgs
	 *
	 * @return void
	 */
	public static function register( $action, array $spec, callable $callback, $priority = 10, $acceptedArgs = 1 ) {
		if ( self::coreAvailable() ) {
			\WPML\Request\Adapter\Ajax::register( $action, self::policy( $spec, $action ), $callback, $priority, $acceptedArgs );

			return;
		}

		add_action( 'wp_ajax_' . $action, $callback, $priority, $acceptedArgs );
		if ( isset( $spec['public'] ) || isset( $spec['machine'] ) ) {
			add_action( 'wp_ajax_nopriv_' . $action, $callback, $priority, $acceptedArgs );
		}
	}

	/**
	 * Ride a third-party host action: the host enforces its own policy; the
	 * callback only adjusts this plugin's context for that request.
	 *
	 * @param string   $hostAction
	 * @param callable $callback
	 * @param string   $reason
	 * @param int      $priority
	 * @param int      $acceptedArgs
	 * @param bool     $alsoNopriv
	 *
	 * @return void
	 */
	public static function listen( $hostAction, callable $callback, $reason, $priority = 10, $acceptedArgs = 1, $alsoNopriv = false ) {
		if ( self::coreAvailable() ) {
			\WPML\Request\Adapter\Ajax::listen( $hostAction, $callback, $reason, $priority, $acceptedArgs, $alsoNopriv );

			return;
		}

		add_action( 'wp_ajax_' . $hostAction, $callback, $priority, $acceptedArgs );
		if ( $alsoNopriv ) {
			add_action( 'wp_ajax_nopriv_' . $hostAction, $callback, $priority, $acceptedArgs );
		}
	}

	/**
	 * @return bool
	 */
	public static function coreAvailable() {
		static $available = null;

		if ( null === $available ) {
			$available = class_exists( '\WPML\Request\Adapter\Ajax' ) && class_exists( '\WPML\Request\Policy\Policy' );
			if ( $available ) {
				// Tell the core gate which files are ours, so a handler this
				// plugin binds without a policy is detached like a core one.
				\WPML\Request\Policy\Registry::ownRoot( dirname( dirname( __DIR__ ) ) );
			}
		}

		return $available;
	}

	/**
	 * @param array  $spec
	 * @param string $action
	 *
	 * @return \WPML\Request\Policy\Policy
	 */
	public static function policy( array $spec, $action ) {
		if ( isset( $spec['nonce'] ) && is_array( $spec['nonce'] ) ) {
			$authenticity = \WPML\Request\Policy\Authenticity::actionNonce( $spec['nonce'][0], isset( $spec['nonce'][1] ) ? $spec['nonce'][1] : 'nonce' );
		} elseif ( isset( $spec['machine'] ) ) {
			$authenticity = null;
		} else {
			$authenticity = \WPML\Request\Policy\Authenticity::none( isset( $spec['no_nonce'] ) ? (string) $spec['no_nonce'] : '' );
		}

		if ( isset( $spec['capability'] ) ) {
			return \WPML\Request\Policy\Policy::capability( $spec['capability'], $authenticity );
		}
		if ( isset( $spec['authorize'] ) ) {
			return \WPML\Request\Policy\Policy::authorize( $spec['authorize'], $authenticity, isset( $spec['description'] ) ? $spec['description'] : '' );
		}
		if ( isset( $spec['authenticated'] ) ) {
			return \WPML\Request\Policy\Policy::authenticated( $authenticity, (string) $spec['authenticated'] );
		}
		if ( isset( $spec['public'] ) ) {
			return \WPML\Request\Policy\Policy::publicAccess( (string) $spec['public'], $authenticity );
		}
		if ( isset( $spec['machine'] ) ) {
			return \WPML\Request\Policy\Policy::machine( $spec['machine'], isset( $spec['description'] ) ? $spec['description'] : '' );
		}

		// Unknown spec: a policy that denies (fail closed), never an open handler.
		return \WPML\Request\Policy\Policy::authorize(
			function () {
				return false;
			},
			\WPML\Request\Policy\Authenticity::none( 'malformed policy spec' ),
			'fail-closed: malformed policy spec for ' . $action
		);
	}
}
