<?php

class LinkCheckTask extends WeeklyTask {
	
	function process() {
		$goodLinks = 0;		// 200-299 HTTP status codes
		$checkLinks = 0;	// 300-399 HTTP status codes
		$brokenLinks = 0;	//	400-599 HTTP status codes
		
		$pages = DataObject::get('Page');
		
		if($pages) {
			foreach($pages as $page) {
				$processor = new LinkCheckProcessor($page->AbsoluteLink());
				$result = $processor->run();
				
				if($result) {
					Debug::show($result);
				}
				
				unset($processor);
				unset($result);
			}
		}
	}
	
}

?>