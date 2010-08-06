<?php
/**
 * @package linkchecker
 * @subpackage tests
 */
class LinkCheckTaskTest extends FunctionalTest {

	public static $fixture_file = 'linkchecker/tests/LinkCheckTaskTest.yml';

	public function testProcess() {
		$task = new LinkCheckTask();
		$task->process();
		$runs = DataObject::get('LinkCheckRun');
		$this->assertEquals(1, $runs->Count());
	}

}