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
	 * Get a {@link LinkCheckRun} record from the DB by ID.
	 *
	 * @return mixed LinkCheckRun|false LinkCheckRun or false if nothing found
	 */
	public function getLinkCheckRun($id) {
		return DataObject::get_by_id('LinkCheckRun', (int) $id);
	}
	
	public function EditForm() {
		$id = $this->urlParams['ID'];
		return $this->getEditForm($id);
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
			new FormAction('doCreate', 'Create link check run'),
			new FormAction('delete', 'Delete')
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
	
	public function doCreate($data, $form) {
		$task = new LinkCheckTask();
		$response = $task->process();
		
		$id = $response['LinkCheckRunID'];
		$date = $response['Date'];
		$class = '';
		
		FormResponse::add("var tree = $('sitetree');");
		FormResponse::add("var newNode = tree.createTreeNode($id, '$date', '$class');");
		FormResponse::add("newNode.selectTreeNode();");
		
		FormResponse::status_message('Created', 'good');

		return FormResponse::respond();
	}
	
	public function doSave($data, $form) {
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
	
	public function delete($request) {
		$runID = $request->param('ID');
		$run = DataObject::get_by_id('LinkCheckRun', (int) $runID);

		// Take the run ID before we delete it, we need this to know what tree node to remove!
		$runID = $run->ID;
		
		$run->delete();
		
		FormResponse::add("var node = $('sitetree').getTreeNodeByIdx('$runID');");
		FormResponse::add("if(node.parentTreeNode)	node.parentTreeNode.removeTreeNode(node);");
		FormResponse::add("$('Form_EditForm').reloadIfSetTo($runID);");
		
		FormResponse::status_message('Deleted','good');

		return FormResponse::respond();
	}
	
	function getsitetree() {
		return $this->renderWith('LinkCheckAdmin_sitetree');
	}	
	
}

?>