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

	static $subitem_class = 'BrokenLink';
	
	/**
	 * Include some required files, like javascript,
	 * for this admin interface when this controller
	 * is created.
	 */
	function init() {
		parent::init();
		
		Requirements::javascript('linkchecker/javascript/LinkCheckAdmin.js');
		
		Requirements::css('linkchecker/css/LinkCheckAdmin.css');
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
	 * Get a {@link LinkCheckRun} record from the DB by ID.
	 *
	 * @return mixed LinkCheckRun|false LinkCheckRun or false if nothing found
	 */
	public function getLinkCheckRun($id) {
		return DataObject::get_by_id('LinkCheckRun', (int) $id);
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
	public function getEditForm($id) {
		$run = $this->getLinkCheckRun($id);
		if(!$run) return false;
		
		// Get the CMS fields for the LinkCheckRun instance,
		// put each field into a CompositeField, so we can
		// just push in any field that happens to be available
		$runCMSFields = $run->getCMSFields();
		$runCompFields = new CompositeField();
		$runCompFields->setID('RunFields');
		if($runCMSFields) foreach($runCMSFields as $runCMSField) {
			$runCompFields->push($runCMSField);
		}
		
		$fields = new FieldSet(
			new TabSet('Root',
				new Tab(
					_t('LinkCheckAdmin.CHECKRUN', 'Link check run'),
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
		
		FormResponse::status_message('Saved', 'good');

		return FormResponse::respond();
	}
	
	public function deleterun() {
		$script = '';
		$ids = split(' *, *', $_REQUEST['csvIDs']);
		$script = '';
		
		if($ids) {
			foreach($ids as $id) {
				if(is_numeric($id)) {
					$record = DataObject::get_by_id($this->stat('tree_class'), $id);
					$record->delete();
					$record->destroy();
					$script .= $this->deleteTreeNodeJS($record);
				}
			}
		}

		$size = sizeof($ids);
		if($size > 1) $message = $size.' '._t('LinkCheckAdmin.FOLDERSDELETED', 'link check runs deleted.');
		else $message = $size.' '._t('LinkCheckAdmin.FOLDERDELETED', 'link check run deleted.');

		$script .= "statusMessage('$message');";
		echo $script;
	}
	
	function getsitetree() {
		return $this->renderWith('LinkCheckAdmin_sitetree');
	}	
	
}

?>