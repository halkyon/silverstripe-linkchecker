<?php
/**
 * @package linkchecker
 * @subpackage tests
 */
class LinkCheckAdminTest extends FunctionalTest {

	public static $fixture_file = 'linkchecker/tests/LinkCheckAdminTest.yml';

	public function setUp() {
		parent::setUp();
		$this->logInWithPermission('ADMIN');
	}

	public function testEditForm() {
		$run = $this->objFromFixture('LinkCheckRun', 'test-run');
		$admin = new LinkCheckAdmin();
		$admin->getEditForm($run->ID);
	}

	public function testDeleteRun() {
		$response = $this->post('admin/linkcheck/deleteRun', array(
			'csvIDs' => $this->idFromFixture('LinkCheckRun', 'test-run')
		));
		$this->assertFalse(DataObject::get_one('LinkCheckRun'));
	}

	public function testSave() {
		$linkcheck = $this->objFromFixture('LinkCheckRun', 'test-run');
		$response = $this->post('admin/linkcheck/EditForm', array(
			'action_save' => 1,
			'LinkCheckRunID' => $linkcheck->ID
		));
	}

	public function testStartRunDoesntHappenWhenExistingRun() {
		$linkcheck = $this->objFromFixture('LinkCheckRun', 'test-run');
		$response = $this->get('admin/linkcheck/startrun');
		$this->assertEquals(1, DataObject::get('LinkCheckRun')->Count());
		$linkcheck->IsComplete = true;
		$linkcheck->write();
		$response = $this->get('admin/linkcheck/startrun');
		$this->assertEquals(2, DataObject::get('LinkCheckRun')->Count());
	}

}