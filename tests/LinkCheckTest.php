<?php

class LinkCheckTest extends SapphireTest {
	
	function testLinkCheckerProcessorCreation() {
		new LinkCheckProcessor('http://www.silverstripe.com/community-overview');
	}
	
}

?>