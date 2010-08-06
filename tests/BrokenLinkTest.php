<?php
/**
 * @package linkchecker
 * @subpackage tests
 */
class BrokenLinkTest extends SapphireTest {

	public static $fixture_file = 'linkchecker/tests/BrokenLinkTest.yml';

	public function testTableOverviewFields() {
		$brokenLink = $this->objFromFixture('BrokenLink', 'broken-link');
		$this->assertEquals(array(
			'PageTitle' => 'Page',
			'Link' => 'Link',
			'Code' => 'Code',
			'Status' => 'Status'
		), $brokenLink->tableOverviewFields());
	}

	public function testGetsPageTitle() {
		$brokenLink = $this->objFromFixture('BrokenLink', 'broken-link');
		$brokenLink->PageID = $this->idFromFixture('Page', 'test-page');
		$brokenLink->write();
		$brokenLink = $this->objFromFixture('BrokenLink', 'broken-link');
		$this->assertEquals($brokenLink->PageTitle, 'Page title');
	}

}