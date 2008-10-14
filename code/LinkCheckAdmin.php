<?php

/**
 * Link checker CMS interface.
 * 
 * @uses LinkCheckRun to create a list of link check runs
 * 
 * @package linkchecker
 */
class LinkCheckAdmin extends LeftAndMain {
	
	/**
	 * Return a link used to access this LinkCheckAdmin
	 * interface in the CMS.
	 *
	 * @param string $action The action to call (defaults to index)
	 * @return string
	 */
	public function Link($action = 'index') {
		return "admin/linkcheck/$action/";
	}
	
	/**
	 * Return all instances of LinkCheckRun from
	 * the database.
	 *
	 * @return DataObjectSet
	 */
	public function LinkCheckRuns() {
		return DataObject::get('LinkCheckRun');
	}
	
}

?>