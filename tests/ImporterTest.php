<?php
/**
 * Tests for BRI_Importer class.
 *
 * @package BusinessReviewImporter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use PHPUnit\Framework\TestCase;

/**
 * Importer test class.
 */
class ImporterTest extends TestCase {
	/**
	 * Importer instance.
	 *
	 * @var BRI_Importer
	 */
	private $importer;

	/**
	 * Set up: create importer with parser.
	 */
	protected function setUp(): void {
		parent::setUp();
		$parser        = new BRI_Parser();
		$this->importer = new BRI_Importer( $parser );
	}

	/**
	 * Call a private method on the importer.
	 *
	 * @param string $name Method name.
	 * @param array  $args Arguments.
	 * @return mixed
	 */
	private function call_private( $name, array $args = array() ) {
		$method = new ReflectionMethod( BRI_Importer::class, $name );
		$method->setAccessible( true );

		return $method->invokeArgs( $this->importer, $args );
	}

	/** @test */
	public function looks_like_html_detects_html_tag() {
		$this->assertTrue( $this->call_private( 'looks_like_html', array( '<html><body>test</body></html>' ) ) );
	}

	/** @test */
	public function looks_like_html_detects_script_tag() {
		$this->assertTrue( $this->call_private( 'looks_like_html', array( 'before <script>alert(1)</script> after' ) ) );
	}

	/** @test */
	public function looks_like_html_detects_next_data() {
		$this->assertTrue( $this->call_private( 'looks_like_html', array( 'prefix __NEXT_DATA__ suffix' ) ) );
	}

	/** @test */
	public function looks_like_html_detects_json_ld() {
		$this->assertTrue( $this->call_private( 'looks_like_html', array( 'application/ld+json is here' ) ) );
	}

	/** @test */
	public function looks_like_html_returns_false_for_plain_text() {
		$this->assertFalse( $this->call_private( 'looks_like_html', array( 'Hello this is just some text' ) ) );
	}

	/** @test */
	public function looks_like_html_returns_false_for_json() {
		$this->assertFalse( $this->call_private( 'looks_like_html', array( '{"key": "value"}' ) ) );
	}

	/** @test */
	public function normalize_import_array_handles_full_items() {
		$items = array(
			array(
				'title'         => 'Great',
				'body'          => 'Amazing service!',
				'author'        => 'John',
				'rating'        => 5,
				'date'          => '2024-01-15',
				'source_url'    => 'https://trustpilot.com/review/1',
				'country'       => 'GB',
				'verified'      => true,
				'featured'      => true,
			),
		);

		$result = $this->call_private( 'normalize_import_array', array( $items ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Great', $result[0]['title'] );
		$this->assertSame( 'Amazing service!', $result[0]['body'] );
		$this->assertSame( 'John', $result[0]['author'] );
		$this->assertSame( 5, $result[0]['rating'] );
		$this->assertSame( '2024-01-15', $result[0]['date'] );
		$this->assertTrue( $result[0]['verified'] );
		$this->assertTrue( $result[0]['featured'] );
	}

	/** @test */
	public function normalize_import_array_handles_text_key_fallback() {
		$items = array(
			array(
				'text'   => 'Good product',
				'author' => 'Jane',
				'rating' => 4,
			),
		);

		$result = $this->call_private( 'normalize_import_array', array( $items ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Good product', $result[0]['body'] );
	}

	/** @test */
	public function normalize_import_array_skips_empty_body() {
		$items = array(
			array(
				'body'   => '',
				'author' => 'John',
			),
			array(
				'body'   => 'Valid review',
				'author' => 'Jane',
			),
		);

		$result = $this->call_private( 'normalize_import_array', array( $items ) );

		$this->assertCount( 1, $result );
	}

	/** @test */
	public function normalize_import_array_handles_reviewer_name_fallback() {
		$items = array(
			array(
				'body'          => 'Nice!',
				'reviewer_name' => 'Alice',
			),
		);

		$result = $this->call_private( 'normalize_import_array', array( $items ) );

		$this->assertCount( 1, $result );
		$this->assertSame( 'Alice', $result[0]['author'] );
	}

	/** @test */
	public function parse_import_block_extracts_rating_body_and_author() {
		$block = "5 stars\nExcellent service\nThe team was fast and friendly.\nJane Smith\n12 May 2026";

		$result = $this->call_private( 'parse_import_block', array( $block ) );

		$this->assertNotNull( $result );
		$this->assertSame( 'Excellent service', $result['title'] );
		$this->assertSame( 'The team was fast and friendly.', $result['body'] );
		$this->assertSame( 'Jane Smith', $result['author'] );
		$this->assertSame( 5.0, $result['rating'] );
		$this->assertSame( '2026-05-12', $result['date'] );
	}

	/** @test */
	public function parse_import_block_returns_null_for_too_few_lines() {
		$this->assertNull( $this->call_private( 'parse_import_block', array( 'Just one line' ) ) );
	}

	/** @test */
	public function parse_import_block_detects_rating_in_different_format() {
		$block = "4.5/5\nGood product\nWould recommend\nMike";

		$result = $this->call_private( 'parse_import_block', array( $block ) );

		$this->assertNotNull( $result );
		$this->assertSame( 4.5, $result['rating'] );
	}

	/** @test */
	public function parse_import_text_parses_json_array() {
		$json = '[{"body": "Review 1", "author": "A", "rating": 5}, {"body": "Review 2", "author": "B", "rating": 4}]';

		$result = $this->importer->parse_import_text( $json );

		$this->assertCount( 2, $result );
		$this->assertSame( 'Review 1', $result[0]['body'] );
		$this->assertSame( 'B', $result[1]['author'] );
	}

	/** @test */
	public function parse_import_text_parses_plain_text_blocks() {
		$text = "5 stars\nGreat\nReally happy with this\nAlice\n10 Jan 2025\n\n4 stars\nGood\nSolid product\nBob\n5 Mar 2025";

		$result = $this->importer->parse_import_text( $text );

		$this->assertCount( 2, $result );
		$this->assertSame( 'Great', $result[0]['title'] );
		$this->assertSame( 'Good', $result[1]['title'] );
	}

	/** @test */
	public function parse_import_text_returns_empty_for_blank_input() {
		$this->assertSame( array(), $this->importer->parse_import_text( '' ) );
		$this->assertSame( array(), $this->importer->parse_import_text( '   ' ) );
	}
}
