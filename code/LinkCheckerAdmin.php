<?php

/**
 * Link checker CMS interface.
 * 
 * This handles the user interface for running
 * instances of the link checker task
 * {@link LinkcheckTask}. For each run instance,
 * a table is generated with the broken link
 * results.
 * 
 * @package linkchecker
 */
class LinkCheckerAdmin extends LeftAndMain {
	
	function Link() {
		return 'admin/linkchecker/$Action/$ID';
	}
	
}

?>