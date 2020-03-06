<?php

/**
 * @author OnTheGo Systems
 */
class Test_Sample extends OTGS_TestCase {
	/**
	 * @test
	 */
	public function it_mocks_a_global_function() {
		WP_Mock::userFunction( 'get_post', [ 'args' => [ 123 ], 'return' => 'It works!', 'times' => 1 ] );

		$this->assertSame( 'It works!', $this->sample_method_to_test_which_uses_get_post() );
	}

	/**
	 * @test
	 */
	public function it_mocks_a_class_method() {
		/** @var \WP_Block_Parser|\PHPUnit_Framework_MockObject_MockObject $block_parser */
		$block_parser = $this->getMockBuilder( '\WP_Block_Parser' )->disableOriginalConstructor()->setMethods( [ 'parse' ] )->getMock();

		$block_parser->expects( $this->exactly( 2 ) )->method( 'parse' )->willReturn( 'It works!', 'It still works!' );

		$this->assertSame( 'It works!', $block_parser->parse( 'whatever' ) );
		$this->assertSame( 'It still works!', $block_parser->parse( 'whatever again' ) );
	}

	/**
	 * @test
	 */
	public function it_mocks_a_class_method_which_calls_a_global_function() {
		/** @var \WP_Block_Parser|\PHPUnit_Framework_MockObject_MockObject $block_parser */
		$block_parser = $this->getMockBuilder( '\WP_Block_Parser' )->disableOriginalConstructor()->setMethods( [ 'parse' ] )->getMock();

		$block_parser->method( 'parse' )->willReturnCallback( function () {
			return $this->sample_method_to_test_which_uses_get_post(s);
		} );

		WP_Mock::userFunction( 'get_post', [ 'args' => [ 123 ], 'return' => 'It works!', 'times' => 1 ] );

		$this->assertSame( 'It works!', $this->sample_method_to_test_which_uses_get_post() );
	}


	private function sample_method_to_test_which_uses_get_post() {
		return get_post( 123 );
	}
}
