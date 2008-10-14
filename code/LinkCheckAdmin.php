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
	 * Return a {@link Form} instance with a
	 * a {@link TableListField} for the current
	 * {@link LinkCheckRun} record that we're currently
	 * looking it, the ID for that LinkCheckRun record
	 * is accessible from the URL as the "ID" parameter.
	 *
	 * @return Form
	 */
	public function EditForm() {
		$fields = new FieldSet();
		$actions = new FieldSet();
		
		// If there's no ID in the URL, just return without doing anything
		$runID = (int) Director::urlParam('ID');
		if(!($runID > 0)) return false;
		
		// Get the LinkCheckRun record from the database
		$run = DataObject::get_by_id('LinkCheckRun', $runID);
		if(!$run->exists()) return false;

		// Add the CMS fields for the LinkCheckRun instance
		$fields->push($run->brokenLinkCMSFields());
		
		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions
		);
		
		return $form;
	}
	
}

?>