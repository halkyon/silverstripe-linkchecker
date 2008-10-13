<?php

class LinkCheckerTest extends SapphireTest {
	
	function testLinkCheckerProcessorCreation() {
		new LinkCheckerProcessor('http://www.silverstripe.com/community-overview');
	}
	
}

?>