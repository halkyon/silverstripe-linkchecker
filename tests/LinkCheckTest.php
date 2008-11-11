<?php

class LinkCheckTest extends SapphireTest {
	
	function testUrlExists() {
		$processor = new LinkCheckProcessor('test');
		$redirectUrl = 'http://doc.silverstripe.com';
		
		$this->assertTrue($processor->urlExists($redirectUrl));
	}
	
	function testSiteDoesExist() {
		$processor = new LinkCheckProcessor('test');
		$redirectUrl = 'http://silverstripe.com';
				
		$this->assertTrue($processor->urlExists($redirectUrl));
	}
	
	function testSiteDoesntExist() {
		$processor = new LinkCheckProcessor('test');
		$redirectUrl = 'http://adfsdfsdfdsfsdfnhabcdaaaaaaaaaabdfsdfsdf.com';
				
		$this->assertFalse($processor->urlExists($redirectUrl));
	}
	
	function testCorrectStatusCodeFor200() {
		$processor = new LinkCheckProcessor('test');
		$brokenUrl = 'http://silverstripe.com';
		
		$headers = $processor->fetchHeaders($brokenUrl);
		
		$status = $processor->extractStatusCode($headers);
		
		$this->assertEquals($status[0], 200);
		$this->assertEquals($status[1], 'OK');
	}
	
	function testCorrectStatusCodeFor404() {
		$processor = new LinkCheckProcessor('test');
		$brokenUrl = 'http://silverstripe.com/a-page-that-does-not-exist';
		
		$headers = $processor->fetchHeaders($brokenUrl);
		
		$status = $processor->extractStatusCode($headers);
		
		$this->assertEquals($status[0], 404);
		$this->assertEquals($status[1], 'Not Found');
	}
	
}

?>