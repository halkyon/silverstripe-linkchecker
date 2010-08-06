<?php
/**
 * @package linkchecker
 * @subpackage tests
 */
class LinkCheckAdminTest extends FunctionalTest {

	public static $fixture_file = 'linkchecker/tests/LinkCheckAdminTest.yml';

	public function testEditForm() {
		$run = $this->objFromFixture('LinkCheckRun', 'test-run');
		$admin = new LinkCheckAdmin();
		$admin->getEditForm($run->ID);
	}

	public function testDeleteRun() {
		$admin = new LinkCheckAdmin();
		$_REQUEST['csvIDs'] = $this->idFromFixture('LinkCheckRun', 'test-run');
		$admin->deleteRun();
		$this->assertFalse(DataObject::get_one('LinkCheckRun'));
	}

}