<?php

declare( strict_types = 1 );

namespace ModernTimeline\Tests\Unit;

use ModernTimeline\Event;
use ModernTimeline\JsonBuilder;
use ModernTimeline\ResultFacade\PropertyValueCollection;
use ModernTimeline\ResultFacade\Subject;
use ModernTimeline\ResultFacade\SubjectCollection;
use ModernTimeline\SlidePresenter\SimpleSlidePresenter;
use PHPUnit\Framework\TestCase;
use SMW\DIWikiPage;
use SMW\Query\PrintRequest;
use SMWDITime;
use Title;

/**
 * @covers \ModernTimeline\JsonBuilder
 * @covers \ModernTimeline\EventExtractor
 */
class JsonBuilderTest extends TestCase {

	private const PAGE_NAME = 'Some Page';

	public function testEmptySubjectCollection() {
		$this->assertBuildsJson(
			[],
			new SubjectCollection( [] )
		);
	}

	public function assertBuildsJson( array $expectedJson, SubjectCollection $input ) {
		$this->assertSame(
			[
				'events' => $expectedJson
			],
			$this->toJson( $input )
		);
	}

	private function toJson( SubjectCollection $input ): array {
		return ( new JsonBuilder( new SimpleSlidePresenter() ) )->buildTimelineJson( $input );
	}

	public function testOnlySubjectsWithNoValues() {
		$this->assertBuildsJson(
			[],
			new SubjectCollection(
				[
					new Subject(
						$this->newDiWikiPage(),
						[]
					)
				]
			)
		);
	}

	private function newDiWikiPage( string $pageName = self::PAGE_NAME ): DIWikiPage {
		$page = $this->createMock( DIWikiPage::class );

		$page->method( 'getTitle' )->willReturn( Title::newFromText( $pageName ) );

		return $page;
	}

	private function newSinglePageWithStartAndEndDate(): SubjectCollection {
		return new SubjectCollection(
			[
				$this->newSubjectWithStartAndEndDate()
			]
		);
	}

	private function newSubjectWithStartAndEndDate(): Subject {
		return new Subject(
			$this->newDiWikiPage(),
			[
				$this->newStartDateValueCollection(),
				$this->newEndDateValueCollection()
			]
		);
	}

	private function newStartDateValueCollection(): PropertyValueCollection {
		return new PropertyValueCollection(
			$this->newDatePrintRequestWithLabel( 'Has date' ),
			[
				$this->newStartDate()
			]
		);
	}

	private function newStartDate(): SMWDITime {
		return new SMWDITime(
			SMWDITime::CM_GREGORIAN,
			2019,
			8,
			2,
			16,
			7,
			42
		);
	}

	private function newEndDateValueCollection(): PropertyValueCollection {
		return new PropertyValueCollection(
			$this->newDatePrintRequestWithLabel( 'End date' ),
			[
				$this->newEndDate()
			]
		);
	}

	private function newEndDate(): SMWDITime {
		return new SMWDITime(
			SMWDITime::CM_GREGORIAN,
			2019,
			8,
			5,
			17,
			39,
			23
		);
	}

	private function newDatePrintRequestWithLabel( string $label ): PrintRequest {
		$pr = $this->createMock( PrintRequest::class );
		$pr->method( 'getLabel' )->willReturn( $label );
		$pr->method( 'getTypeID' )->willReturn( '_dat' );
		return $pr;
	}

	public function testStartDate() {
		$json = $this->toJson( $this->newSinglePageWithStartAndEndDate() );

		$this->assertSame(
			[
				'year' => 2019,
				'month' => 8,
				'day' => 2,
				'hour' => 16,
				'minute' => 7,
				'second' => 42,
			],
			$json['events'][0]['start_date']
		);
	}

	public function testEndDate() {
		$json = $this->toJson( $this->newSinglePageWithStartAndEndDate() );

		$this->assertSame(
			[
				'year' => 2019,
				'month' => 8,
				'day' => 5,
				'hour' => 17,
				'minute' => 39,
				'second' => 23,
			],
			$json['events'][0]['end_date']
		);
	}

	public function testHeadline() {
		$json = $this->toJson( $this->newSinglePageWithStartAndEndDate() );

		$this->assertContains(
			self::PAGE_NAME,
			$json['events'][0]['text']['headline']
		);
	}

	public function testPageWithStartAndEndDateOnlyLeadsToOneEvent() {
		$this->assertCount(
			1,
			$this->toJson( $this->newSinglePageWithStartAndEndDate() )['events']
		);
	}

	public function testEventWithoutImageHasNoMedia() {
		$event = new Event(
			$this->newSubjectWithStartAndEndDate(),
			$this->newStartDate(),
			$this->newEndDate()
		);

		$this->assertArrayNotHasKey(
			'media',
			$this->newJsonBuilder()->buildEvent( $event )
		);
	}

	private function newJsonBuilder(): JsonBuilder {
		return new JsonBuilder( new SimpleSlidePresenter() );
	}

}
