<?php

/**
 * A BrokenLink is a simple DataObject that
 * represents a single broken link on a page.
 * 
 * It has a parent {@link LinkCheckRun} which
 * contains many {@link BrokenLink} records
 * related to it through a one-to-many relation.
 * 
 * It also has a {@link Page} record related to
 * it which is the page for where this broken
 * link was found. We record this so the CMS user
 * can easily discover where to correct the
 * broken link!
 * 
 * @uses LinkCheckRun
 * @uses Page
 * 
 * @package linkchecker
 */
class BrokenLink extends DataObject {
	
	static $db = array(
		'Link' => 'Varchar(255)',
		'Code' => 'Int',
		'Status' => 'Varchar(20)'
	);
	
	/**
	 * A BrokenLink has a single {@link LinkCheckRun}
	 * as well as a single {@link Page}. LinkCheckRun
	 * is the "parent" for the batch run for broken
	 * links, and Page is the {@link Page} record that
	 * the broken link was found on.
	 *
	 * @var array
	 */
	static $has_one = array(
		'LinkCheckRun' => 'LinkCheckRun',
		'Page' => 'Page'
	);
	
}

?>