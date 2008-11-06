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
	
	public function show($params) {
		if($params['ID']) $this->setCurrentPageID($params['ID']);
		if(isset($params['OtherID']))
			Session::set('currentMember', $params['OtherID']);

		if(Director::is_ajax()) {
			SSViewer::setOption('rewriteHashlinks', false);
			return $this->EditForm() ? $this->EditForm()->formHtmlContent() : false;

		} else {
			return array();
		}
	}	
	
	/**
	 * Return a link used to access this LinkCheckAdmin
	 * interface in the CMS.
	 *
	 * @param string $action The action to call (defaults to index)
	 * @return string
	 */
	public function Link($action = null) {
		return "admin/linkcheck/$action";
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
		
		$runCMSFields = $run->getCMSFields();
		
		$runCompFields = new CompositeField();
		$runCompFields->setID('RunFields');
		
		if($runCMSFields) foreach($runCMSFields as $runCMSField) {
			$runCompFields->push($runCMSField);
		}
		
		$brokenLinkCount = ($run->BrokenLinks()) ? $run->BrokenLinks()->Count() : 0;

		if($brokenLinkCount == 1) {
			$resultNumField = new LiteralField('ResultNo', '<p>1 broken link was found</p>');
		} elseif($brokenLinkCount > 0) {
			$resultNumField = new LiteralField('ResultNo', "<p>$brokenLinkCount broken links were found</p>");
		} else {
			$resultNumField = new LiteralField('ResultNo', '<p>No broken links were found</p>');
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
		
		if(!empty($result['LinkCheckRunID'])) {
			$run = DataObject::get_by_id('LinkCheckRun', (int) $result['LinkCheckRunID']);
			if($run) echo $this->addTreeNodeJS($run, true);
		}
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
		if($size > 1) $message = $size . ' ' . _t('LinkCheckAdmin.FOLDERSDELETED', 'link check runs deleted');
		else $message = $size . ' ' . _t('LinkCheckAdmin.FOLDERDELETED', 'link check run deleted');

		$script .= "statusMessage('$message');";
		echo $script;
	}
	
	public function SiteTreeAsUL() {
		$obj = singleton('LinkCheckRun');
		$obj->setMarkingFilter('ClassName', 'LinkCheckRun');
		$obj->markPartialTree();

		if($p = $this->currentPage()) $obj->markToExpose($p);

		// getChildrenAsUL is a flexible and complex way of traversing the tree
		$siteTreeUL = $obj->getChildrenAsUL(
			'',
			'"<li id=\"$child->ID\" class=\"$child->class" . $child->markingClasses() .  ($extraArg->isCurrentPage($child) ? " current" : "") . "\">" . ' .
			'"<a href=\"" . Director::link(substr($extraArg->Link(),0,-1), "show", $child->ID) . "\" class=\"" . ($child->hasChildren() ? " contents" : "") . "\" >" . $child->TreeTitle() . "</a>"',
			$this,
			true
		);
		
		// Wrap the root if needs be.
		$rootLink = $this->Link();
		if(!isset($rootID)) {
			$siteTree = "<ul id=\"sitetree\" class=\"tree unformatted\"><li id=\"record-root\" class=\"Root\"><a href=\"$rootLink\"><strong>Run dates</strong></a>";
			$siteTree .= $siteTreeUL . "</li></ul>";
		}

		return $siteTree;
	}
	
}

?>