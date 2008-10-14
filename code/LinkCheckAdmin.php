<?php

/**
 * Link checker CMS interface.
 * 
 * @package linkchecker
 */
class LinkCheckAdmin extends LeftAndMain {
	
	public function Link($action = 'index') {
		return "admin/linkcheck/$action/";
	}
	
	public function LinkCheckRuns() {
	}
	
}

?>