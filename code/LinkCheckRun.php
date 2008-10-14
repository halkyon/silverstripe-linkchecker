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
	 * Return CMS fields suitable for editing an
	 * instance of LinkCheckRun, including linked
	 * instances of {@link Member} and {@link BrokenLink}.
	 * 
	 * @return FieldSet
	 */
	public function getCMSFields() {
		
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
		
		// Set permissions (we don't want to allow adding)
		$table->setPermissions(array(
			'delete',
			'export'
		));
		
		// Get all Member records in the DB, and source for DropdownField
		$members = DataObject::get('Member');
		$membersSource = $members->toDropDownMap('ID', 'FirstName');
		
		$fields = new FieldSet(
			new DropdownField(
				'MemberID',
				'Member',
				$membersSource ? $membersSource : array()
			),
			$table
		);
		
		return $fields;
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
		
		if($this->BrokenLinks() && ($this->BrokenLinks()->Count() > 0)) {
			foreach($this->BrokenLinks() as $brokenLink) {
				$brokenLink->delete();
			}
		}
	}
	
}

?>