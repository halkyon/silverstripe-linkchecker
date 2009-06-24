<?php
/**
 * LinkCheckRun is a simple DataObject that
 * represents a single run of the link checker
 * process, done using {@link LinkCheckTask}.
 * 
 * For every broken link, or link that needs to
 * be checked (redirects), it has a one-to-many
 * relationship with BrokenLink, to represent
 * a single broken link that this run has found.
 * 
 * @uses Member
 * @uses BrokenLink
 * 
 * @package linkchecker
 */
class LinkCheckRun extends DataObject {
	
	static $db = array(
		'IsComplete' => 'Boolean',
		'PagesChecked' => 'Int',
		'FinishDate' => 'SSDatetime'
	);
	
	/**
	 * A LinkCheckRun can have a {@link Member}
	 * record that was the CMS user who invoked
	 * the broken link check run.
	 * 
	 * @var array
	 */
	static $has_one = array(
		'Member' => 'Member'
	);

	/**
	 * A LinkCheckRun has many {@link BrokenLink}
	 * records related to it, which contain what
	 * page the broken link was on.
	 *
	 * @var array
	 */
	static $has_many = array(
		'BrokenLinks' => 'BrokenLink'
	);
	
	/**
	 * Check there are no broken links for this link check
	 * run or not.
	 *
	 * @return boolean
	 */
	public function NoBrokenLinks() {
		$brokenLinks = $this->BrokenLinks();
		if($brokenLinks && ($brokenLinks->Count() > 0)) return false;
		else return true;
	}
	
	/**
	 * This field is used for the site tree in the
	 * CMS.
	 *
	 * @return string
	 */
	public function TreeTitle() {
		return $this->obj('Created')->Nice();
	}
	
	/**
	 * Return CMS fields suitable for editing an
	 * instance of LinkCheckRun, including linked
	 * instances of {@link Member} and {@link BrokenLink}.
	 * 
	 * @return FieldSet
	 */
	public function getCMSFields() {
		$fields = new FieldSet();
		$brokenLinks = $this->BrokenLinks();
		
		if($brokenLinks && ($brokenLinks->Count() > 0)) {
			$table = $this->brokenLinksTable();
			$fields->push($table);
		}
		
		return $fields;
	}

	/**
	 * Return a TableListField for viewing the related
	 * BrokenLink records through the one-to-many relation.
	 *
	 * @return TableListField
	 */
	public function brokenLinksTable() {
		// Get a singleton instance of BrokenLink
		$SNG_brokenLink = singleton('BrokenLink');
		
		// Set up the TableListField, for viewing BrokenLink
		// records that have the current LinkCheckRun ID
		$table = new TableListField(
			'BrokenLinks',
			'BrokenLink',
			$SNG_brokenLink->tableOverviewFields(),
			"LinkCheckRunID = $this->ID"
		);
		
		$table->setFieldFormatting(array(
			'PageTitle' => '<a href=\"admin/show/$PageID\">$PageTitle</a>',
			'Link' => '<a href=\"$Link\">$Link</a>'
		));
		
		// Set permissions (we don't want to allow adding)
		$table->setPermissions(array(
			'export'
		));
		
		$table->setPageSize(20);
		$table->setShowPagination(true);
		
		return $table;
	}
	
	/**
	 * Before writing, record the member who
	 * ran this task, if applicable.
	 */
	public function onBeforeWrite() {
		parent::onBeforeWrite();
		$this->MemberID = Member::currentUserID() ? Member::currentUserID() : 0;
	}
	
	/**
	 * Before deleting this LinkCheckRun, delete
	 * any {@link BrokenLink} records that are
	 * related to this instance through the one-to-many
	 * relation.
	 */
	public function onBeforeDelete() {
		parent::onBeforeDelete();
		$brokenLinks = $this->BrokenLinks();
		if($brokenLinks && ($brokenLinks->Count() > 0)) {
			foreach($brokenLinks as $brokenLink) {
				$brokenLink->delete();
			}
		}
	}

}