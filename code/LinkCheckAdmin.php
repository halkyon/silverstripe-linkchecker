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

	/**
	 * Return a {@link TableListField} for the current
	 * {@link LinkCheckRun} record that we're currently
	 * looking it, the ID for that LinkCheckRun record
	 * is accessible from the URL as the "ID" parameter.
	 *
	 * @return TableListField
	 */
	public function EditForm() {
		$runID = (int) Director::urlParam('ID');
		if(!$runID) return false;
		
		$SNG_brokenLink = singleton('BrokenLink');
		
		$table = new TableListField(
			'BrokenLinks',
			'BrokenLink',
			$SNG_brokenLink->tableOverviewFields(),
			"LinkCheckRunID = $runID"
		);
		
		$table->setPermissions(array(
			'delete',
			'export'
		));
		
		return $table;
	}
	
}

?>