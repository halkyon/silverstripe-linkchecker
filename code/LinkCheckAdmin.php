<?php

/**
 * Link checker CMS interface.
 * 
 * Provides a CMS interface for {@link LinkCheckRun}
 * DataObjects, so that a user can manage them. Each
 * {@link LinkCheckRun} has a one-to-many relationship
 * with {@link BrokenLink} which represents a single
 * broken link on a link check "run".
 * 
 * @uses LinkCheckRun to create a list of link check runs
 * 
 * @package linkchecker
 */
class LinkCheckAdmin extends LeftAndMain {
	
	static $tree_class = 'LinkCheckRun';
	
	static $allowed_actions = array(
		'save',
		'BrokenLinks'
	);
	
	/**
	 * Include some required files, like javascript,
	 * for this admin interface when this controller
	 * is created.
	 */
	function init() {
		parent::init();
		
		Requirements::javascript('linkchecker/javascript/LinkCheckAdmin_left.js');
		Requirements::javascript('linkchecker/javascript/LinkCheckAdmin_right.js');
	}
	
	/**
	 * Return a link used to access this LinkCheckAdmin
	 * interface in the CMS.
	 *
	 * @param string $action The action to call (defaults to index)
	 * @return string
	 */
	public function Link() {
		return 'admin/linkcheck';
	}
	
	/**
	 * Return all instances of LinkCheckRun from
	 * the database, sorted by the creation date first.
	 *
	 * @return DataObjectSet
	 */
	public function LinkCheckRuns() {
		return DataObject::get('LinkCheckRun', '', 'Created DESC');
	}

	/**
	 * Get a {@link LinkCheckRun} record from the DB
	 * which is specified by the URL parameter "ID".
	 *
	 * @return mixed LinkCheckRun|false LinkCheckRun or false if nothing found
	 */
	public function getLinkCheckRun() {
		// If there's no ID in the URL, just return without doing anything
		$runID = (int) Director::urlParam('ID');
		if(!($runID > 0)) return false;
		
		// Get the LinkCheckRun record from the database
		$run = DataObject::get_by_id('LinkCheckRun', $runID);
		if(!$run->exists()) return false;
		
		return $run;
	}
	
	/**
	 * Return a {@link Form} instance with a
	 * a {@link TableListField} for the current
	 * {@link LinkCheckRun} record that we're currently
	 * looking it, the ID for that LinkCheckRun record
	 * is accessible from the URL as the "ID" parameter.
	 * 
	 * @uses LinkCheckAdmin->getLinkCheckRun()
	 * 
	 * @return Form
	 */
	public function EditForm() {
		$run = $this->getLinkCheckRun();
		if(!$run) return false;
		
		// Get the CMS fields for the LinkCheckRun instance,
		// put each field into a CompositeField, so we can
		// just push in any field that happens to be available
		$runCMSFields = $run->getCMSFields();
		$runCompFields = new CompositeField();
		if($runCMSFields) foreach($runCMSFields as $runCMSField) {
			$runCompFields->push($runCMSField);
		}
		
		$fields = new FieldSet(
			new TabSet('Root',
				new Tab(
					_t('LinkCheckAdmin.CHECKRUN','Link check run'),
					$runCompFields
				)
			)
		);
		
		$fields->push(new HiddenField('LinkCheckRunID', '', $run->ID));
		$fields->push(new HiddenField('ID', '', $run->ID));

		$actions = new FieldSet(
			new FormAction('save', 'Save')
		);
		
		$form = new Form(
			$this,
			'EditForm',
			$fields,
			$actions
		);
		
		$form->loadDataFrom($run);
		
		return $form;
	}
	
	public function save($data, $form) {
		$validationErrors = false;
		
		$run = DataObject::get_by_id('LinkCheckRun', (int) $data['LinkCheckRunID']);
		if(!$run) $validationErrors = true;
		
		if($validationErrors) {
			Director::redirectBack();
			return false;
		}
		
		$form->saveInto($run);
		$run->write();
		
		echo <<<JS
			statusMessage("Saved.");
JS;
	}
	
}

?>