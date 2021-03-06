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
 * @todo Use ModelAdmin instead of specific LeftAndMain subclass
 * 
 * @package linkchecker
 */
class LinkCheckAdmin extends LeftAndMain {
	
	static $url_segment = 'linkcheck';
	
	static $url_rule = '/$Action/$ID';
	
	static $menu_title = 'Link Checker';
	
	public static $tree_class = 'LinkCheckRun';

	public function init() {
		parent::init();
		Requirements::javascript('linkchecker/javascript/LinkCheckAdmin.js');
		Requirements::css('linkchecker/css/LinkCheckAdmin.css');
	}
	
	public function show($params) {
		if($params['ID']) $this->setCurrentPageID($params['ID']);
		if(isset($params['OtherID'])) Session::set('currentMember', $params['OtherID']);

		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			return $this->EditForm() ? $this->EditForm()->formHtmlContent() : false;
		}
		
		return array();
	}
	
	public function Link($action = null) {
		return "admin/linkcheck/$action";
	}
		
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
	 * @todo Fix up the hardcoded english strings
	 * @todo Split functionality to separate methods
	 * 
	 * @return Form
	 */
	public function getEditForm($id = null) {
		$run = $this->getLinkCheckRun($id);
		if(!$run) return false;
		
		$runCMSFields = $run->getCMSFields();
		
		$runCompFields = new CompositeField();
		$runCompFields->setID('RunFields');
		
		if($runCMSFields) foreach($runCMSFields as $runCMSField) {
			$runCompFields->push($runCMSField);
		}
		
		$brokenLinkCount = ($run->BrokenLinks()) ? $run->BrokenLinks()->Count() : 0;

		$finishedDate = $run->obj('FinishDate')->Nice();
		$pagesChecked = $run->PagesChecked;

		if($run->IsComplete) {	// Run is complete
			if($brokenLinkCount == 1) {
				$resultNumField = new LiteralField('ResultNo', "<p>Finished at {$finishedDate}. $pagesChecked pages were checked. 1 broken link was found.</p>");
			} elseif($brokenLinkCount > 0) {
				$resultNumField = new LiteralField('ResultNo', "<p>Finished at {$finishedDate}. $pagesChecked pages were checked. $brokenLinkCount broken links were found.</p>");
			} else {
				$resultNumField = new LiteralField('ResultNo', "<p>Finished at {$finishedDate}. $pagesChecked pages were checked. No broken links were found.</p>");
			}
			
		} else {	// Run not completed yet
			if($brokenLinkCount == 1) {
				$resultNumField = new LiteralField('ResultNo', '<p>This link check run is not completed yet. 1 broken link found so far.</p>');
			} elseif($brokenLinkCount > 0) {
				$resultNumField = new LiteralField('ResultNo', "<p>This link check run is not completed yet. $brokenLinkCount broken links found so far.</p>");
			} else {
				$resultNumField = new LiteralField('ResultNo', '<p>This link check run is not completed yet. No broken links found so far.</p>');
			}
		}
		
		$runDate = $run->obj('Created')->Nice();
		
		$fields = new FieldSet(
			new TabSet('Root',
				new Tab(
					_t('LinkCheckAdmin.CHECKRUN', 'Results'),
					new HeaderField('Link check run', 2),
					new LiteralField('ResultText', "<p>Run at {$runDate}</p>"),
					$resultNumField,
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
	
	public function startrun() {
		$task = new LinkCheckTask();
		$result = $task->process();
		$script = '';
		
		if(class_exists('SapphireTest', false) && SapphireTest::is_running_test()) return;
		
		if(!$result) {
			FormResponse::status_message(
				'There is already a link check running at the moment. Please wait for it to complete before starting a new one.',
				'bad'
			);
			
			return FormResponse::respond();
		}
		
		if(!empty($result['LinkCheckRunID'])) {
			$run = DataObject::get_by_id('LinkCheckRun', (int) $result['LinkCheckRunID']);
			if($run) echo $this->addTreeNodeJS($run, true);
		}
	}
	
	public function deleterun() {
		$script = '';
		$ids = split(' *, *', $_REQUEST['csvIDs']);
		
		if($ids) {
			foreach($ids as $id) {
				if(is_numeric($id)) {
					$record = DataObject::get_by_id('LinkCheckRun', $id);
					// Compatibility with 3.0
					if($this->hasMethod('deleteTreeNodeJS')) $script .= $this->deleteTreeNodeJS($record);
					$record->delete();
					$record->destroy();
				}
			}
		}

		$size = sizeof($ids);
		if($size > 1) $message = $size . ' ' . _t('LinkCheckAdmin.FOLDERSDELETED', 'link check runs deleted');
		else $message = $size . ' ' . _t('LinkCheckAdmin.FOLDERDELETED', 'link check run deleted');

		$script .= "statusMessage('$message');";
		
		if(class_exists('SapphireTest', false) && SapphireTest::is_running_test()) return;
		
		echo $script;
	}
	
}