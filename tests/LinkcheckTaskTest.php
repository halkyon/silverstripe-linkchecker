<?php

class LinkCheckerTest extends SapphireTest {
	
	function testLinkCheckerProcessorCreation() {
		new LinkCheckProcessor('http://www.silverstripe.com/community-overview');
	}
	
}

?>