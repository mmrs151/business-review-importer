<?php
/**
 * Tests for pure helper methods in Truspilot_Review_Renderer.
 *
 * @package TruspilotReview
 */

use PHPUnit\Framework\TestCase;

/**
 * Helper test class.
 */
class HelpersTest extends TestCase {
	/**
	 * Renderer instance.
	 *
	 * @var Truspilot_Review_Renderer
	 */
	private $renderer;

	/**
	 * Set up: grab plugin singleton via reflection and create renderer.
	 */
	protected function setUp(): void {
		parent::setUp();

		$ref            = new ReflectionMethod( Truspilot_Review_Plugin::class, 'instance' );
		$plugin         = $ref->invoke( null );
		$this->renderer = new Truspilot_Review_Renderer( $plugin );
	}

	/**
	 * Call a private/protected method on the renderer.
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @return mixed
	 */
	private function call_private( $name, array $args = array() ) {
		$method = new ReflectionMethod( Truspilot_Review_Renderer::class, $name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->renderer, $args );
	}

	/** @test */
	public function to_bool_returns_true_for_truthy_strings() {
		$this->assertTrue( $this->call_private( 'to_bool', array( 'true' ) ) );
		$this->assertTrue( $this->call_private( 'to_bool', array( '1' ) ) );
		$this->assertTrue( $this->call_private( 'to_bool', array( 'yes' ) ) );
		$this->assertTrue( $this->call_private( 'to_bool', array( 1 ) ) );
		$this->assertTrue( $this->call_private( 'to_bool', array( true ) ) );
	}

	/** @test */
	public function to_bool_returns_false_for_falsy_values() {
		$this->assertFalse( $this->call_private( 'to_bool', array( 'false' ) ) );
		$this->assertFalse( $this->call_private( 'to_bool', array( '0' ) ) );
		$this->assertFalse( $this->call_private( 'to_bool', array( '' ) ) );
		$this->assertFalse( $this->call_private( 'to_bool', array( 'no' ) ) );
		$this->assertFalse( $this->call_private( 'to_bool', array( 0 ) ) );
		$this->assertFalse( $this->call_private( 'to_bool', array( false ) ) );
	}

	/** @test */
	public function sanitize_choice_returns_valid_value() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'carousel', array( 'carousel', 'grid', 'list', 'wall' ), 'carousel' )
		);
		$this->assertSame( 'carousel', $result );
	}

	/** @test */
	public function sanitize_choice_returns_fallback_for_invalid_value() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'invalid', array( 'carousel', 'grid', 'list', 'wall' ), 'grid' )
		);
		$this->assertSame( 'grid', $result );
	}

	/** @test */
	public function sanitize_choice_sanitizes_key() {
		$result = $this->call_private(
			'sanitize_choice',
			array( ' CAROUSEL! ', array( 'carousel' ), 'list' )
		);
		$this->assertSame( 'list', $result );
	}

	/** @test */
	public function sanitize_choice_accepts_list() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'list', array( 'carousel', 'grid', 'list', 'wall' ), 'carousel' )
		);
		$this->assertSame( 'list', $result );
	}

	/** @test */
	public function sanitize_choice_accepts_wall() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'wall', array( 'carousel', 'grid', 'list', 'wall' ), 'carousel' )
		);
		$this->assertSame( 'wall', $result );
	}

	/** @test */
	public function sanitize_choice_accepts_wall_style_standard() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'standard', array( 'standard', 'noticeboard' ), 'standard' )
		);
		$this->assertSame( 'standard', $result );
	}

	/** @test */
	public function sanitize_choice_accepts_wall_style_noticeboard() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'noticeboard', array( 'standard', 'noticeboard' ), 'standard' )
		);
		$this->assertSame( 'noticeboard', $result );
	}

	/** @test */
	public function render_stars_contains_five_spans() {
		$html = $this->call_private( 'render_stars', array( 4.2 ) );

		$this->assertStringContainsString( '<span', $html );
		$this->assertSame( 5, preg_match_all( '/<span/', $html ) );
		$this->assertSame( 5, preg_match_all( '/&#9733;/', $html ) );
	}

	/** @test */
	public function render_stars_fills_correct_number() {
		$html = $this->call_private( 'render_stars', array( 3.8 ) );
		$this->assertSame( 4, preg_match_all( '/is-filled/', $html ) );

		$html2 = $this->call_private( 'render_stars', array( 5.0 ) );
		$this->assertSame( 5, preg_match_all( '/is-filled/', $html2 ) );

		$html3 = $this->call_private( 'render_stars', array( 1.0 ) );
		$this->assertSame( 1, preg_match_all( '/is-filled/', $html3 ) );
	}

	/** @test */
	public function has_profile_summary_returns_false_for_empty() {
		$result = $this->call_private(
			'has_profile_summary',
			array(
				array(
					'trust_score'   => 0,
					'star_rating'   => 0,
					'total_reviews' => 0,
				),
			)
		);
		$this->assertFalse( $result );
	}

	/** @test */
	public function has_profile_summary_returns_true_with_trust_score() {
		$result = $this->call_private(
			'has_profile_summary',
			array(
				array(
					'trust_score'   => 4.5,
					'star_rating'   => 0,
					'total_reviews' => 0,
				),
			)
		);
		$this->assertTrue( $result );
	}

	/** @test */
	public function has_profile_summary_returns_true_with_total_reviews() {
		$result = $this->call_private(
			'has_profile_summary',
			array(
				array(
					'trust_score'   => 0,
					'star_rating'   => 0,
					'total_reviews' => 142,
				),
			)
		);
		$this->assertTrue( $result );
	}

	/** @test */
	public function sanitize_choice_returns_default_for_invalid() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'diagonal', array( 'vertical', 'horizontal' ), 'vertical' )
		);
		$this->assertSame( 'vertical', $result );
	}

	/** @test */
	public function sanitize_choice_returns_valid_horizontal() {
		$result = $this->call_private(
			'sanitize_choice',
			array( 'horizontal', array( 'vertical', 'horizontal' ), 'vertical' )
		);
		$this->assertSame( 'horizontal', $result );
	}
}
