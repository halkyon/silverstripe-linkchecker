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
	 * Before writing, record the member who
	 * ran this task, if applicable.
	 */
	function onBeforeWrite() {
		$this->MemberID = Member::currentUserID() ? Member::currentUserID() : 0;
	}
	
	/**
	 * Before deleting this LinkCheckRun, delete
	 * any {@link BrokenLink} records that are
	 * related to this instance through the one-to-many
	 * relation.
	 */
	function onBeforeDelete() {
		if($this->BrokenLinks() && ($this->BrokenLinks()->Count() > 0)) {
			foreach($this->BrokenLinks() as $brokenLink) {
				$brokenLink->delete();
			}
		}
	}
	
}

?>