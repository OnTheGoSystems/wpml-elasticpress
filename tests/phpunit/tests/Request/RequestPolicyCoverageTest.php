<?php

namespace WPML\ElasticPress\Request;

/**
 * Architectural test: every admin-ajax / admin-post / REST / XML-RPC binding
 * in this plugin goes through the request-policy adapter
 * (`WPML\ElasticPress\Request\Ajax`), never through a raw registration. The
 * universe is the plugin's own PHP tree; a new handler bound without a policy
 * fails here.
 *
 * @group request-policy
 */
class RequestPolicyCoverageTest extends \PHPUnit\Framework\TestCase {

	const SHIM = 'src/Request/Ajax.php';

	/** Directories that are not this plugin's code. */
	const EXCLUDED_DIRS = [ 'vendor', 'vendor-bin', 'node_modules', 'tests', '.git', 'res', 'dist', 'build' ];

	const BINDING = '/(?:add_action|Hooks::onAction|add_ajax_action)\s*\(\s*(?:\'(?:wp_ajax_|admin_post_|wpmuadminedit)|"(?:wp_ajax_|admin_post_|wpmuadminedit))/';

	/** @test */
	public function every_request_transport_binding_goes_through_the_adapter() {
		$root      = dirname( __DIR__, 4 );
		$offenders = [];
		$files     = 0;

		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveCallbackFilterIterator(
				new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ),
				function ( \SplFileInfo $current ) {
					if ( $current->isDir() ) {
						return ! in_array( $current->getFilename(), self::EXCLUDED_DIRS, true );
					}

					return 'php' === $current->getExtension();
				}
			)
		);

		foreach ( $iterator as $file ) {
			$files++;
			$relative = ltrim( str_replace( $root, '', (string) $file ), '/' );
			if ( self::SHIM === $relative ) {
				continue;
			}
			$source = self::withoutComments( (string) file_get_contents( (string) $file ) );
			if ( preg_match_all( self::BINDING, $source, $matches ) ) {
				foreach ( $matches[0] as $match ) {
					$offenders[] = $relative . ': ' . trim( $match );
				}
			}
			if ( false !== strpos( $source, 'register_rest_route(' ) && false === strpos( $source, 'Rest::permission(' ) ) {
				$offenders[] = $relative . ': register_rest_route() without a Policy permission callback';
			}
			if ( false !== strpos( $source, "'xmlrpc_methods'" ) && false === strpos( $source, 'XmlRpc::method(' ) ) {
				$offenders[] = $relative . ': xmlrpc_methods entries not bound through XmlRpc::method()';
			}
		}

		$this->assertGreaterThan( 0, $files, 'the plugin tree must be enumerated' );
		$this->assertSame(
			[],
			$offenders,
			"These request-transport bindings declare no policy; bind them through " . self::SHIM . " (register()/listen()):\n - " . implode( "\n - ", $offenders )
		);
	}

	/** @test */
	public function the_checker_catches_a_raw_binding() {
		$this->assertSame( 1, preg_match( self::BINDING, "add_action( 'wp_ajax_x', [ \$this, 'cb' ] );" ) );
		$this->assertSame( 1, preg_match( self::BINDING, "add_action( 'wp_ajax_nopriv_x', [ \$this, 'cb' ] );" ) );
		$this->assertSame( 1, preg_match( self::BINDING, "add_action(\n\t'wp_ajax_' . self::ACTION,\n\t[ \$this, 'cb' ] );" ) );
		$this->assertSame( 1, preg_match( self::BINDING, "add_action( 'admin_post_x', 'cb' );" ) );
		$this->assertSame( 1, preg_match( self::BINDING, "Hooks::onAction( 'wp_ajax_elementor_ajax' )" ) );
		$this->assertSame( 0, preg_match( self::BINDING, "\\WPML\\ElasticPress\\Request\\Ajax::register( 'x', [ 'capability' => 'c' ], [ \$this, 'cb' ] );" ) );
		$this->assertSame( 0, preg_match( self::BINDING, "add_action( 'init', [ \$this, 'cb' ] );" ) );
	}

	private static function withoutComments( $source ) {
		$out = '';
		foreach ( token_get_all( $source ) as $token ) {
			if ( is_array( $token ) ) {
				if ( in_array( $token[0], [ T_COMMENT, T_DOC_COMMENT ], true ) ) {
					continue;
				}
				$out .= $token[1];
			} else {
				$out .= $token;
			}
		}

		return $out;
	}
}
