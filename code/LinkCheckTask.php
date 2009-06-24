<?php
/**
 * This is a task designed to be run weekly
 * that goes through each {@link Page} instance
 * on a SilverStripe site, and determines what
 * links are broken, creating a new {@link LinkCheckRun}
 * containing many {@link BrokenLink} records
 * related to it, through a one-to-many relationship.
 * 
 * @uses LinkCheckRun
 * @uses BrokenLink
 * @uses LinkCheckProcessor
 * 
 * @package linkchecker
 */
class LinkCheckTask extends WeeklyTask {
	
	/**
	 * Ignore these page type classes when
	 * checking pages for broken links.
	 * 
	 * @var array
	 */
	protected static $exempt_classes = array(
		'ErrorPage',
		'ForumPage',
		'RedirectorPage'
	);
	
	/**
	 * Add a class to the exempt page types array.
	 * @param string $class The class to add
	 */
	public static function add_extempt_class($class) {
		if(!in_array($class, self::$exempt_classes)) {
			self::$exempt_classes[] = $class;
		}
	}

	/**
	 * Remove a class from the exempt page types array.
	 * @param string $class The class to remove
	 */
	public static function remove_exempt_class($class) {
		foreach(self::$exempt_classes as $index => $exemptClass) {
			if($exemptClass == $class) unset(self::$exempt_classes[$index]);
		}
	}
	
	/**
	 * Run the LinkCheckTask.
	 * @todo Split functionality to separate methods
	 */
	public function process() {
		echo "\r\n";
		
		if(!ClassInfo::hasTable('LinkCheckRun')) {
			if(!Director::is_ajax()) {
				echo "Database has not been built. Please run dev/build first!\r\n";
			}
			return false;
		}
		
		// If there is already a LinkCheckRun that exists and is not complete,
		// don't allow a new run as it could run the server to the ground!
		// @todo we probably want some system that allows cancelling a check halfway through
		if(DataObject::get_one('LinkCheckRun', 'IsComplete = 0')) {
			if(!Director::is_ajax()) {
				echo "There is already a link check running at the moment. Please wait for it to complete before starting a new one.\r\n";
			}
			return false;
		}
		
		set_time_limit(0);
		ini_set('max_execution_time', 0);
		
		$goodLinks = 0;   // 200-299 HTTP status codes
		$checkLinks = 0;  // 300-399 HTTP status codes
		$brokenLinks = 0; // 400-599 HTTP status codes
		
		$pages = DataObject::get('SiteTree');
		if(!$pages) return false;
		
		$run = new LinkCheckRun(); // We have started a new run, create the object and write it
		$run->write();
		
		$pagesChecked = 0;
		foreach($pages as $page) {
			if(in_array($page->class, self::$exempt_classes)) {
				break;
			}
			
			$processor = new LinkCheckProcessor($page->AbsoluteLink());
			if(Director::is_ajax()) $processor->showMessages = false;
			$results = $processor->run();
			
			if($results) foreach($results as $result) {
				if($result['Code'] >= 200 && $result['Code'] <= 299) {
					$goodLinks++;
				} elseif($result['Code'] >= 300 && $result['Code'] <= 399) {
					$checkLinks++;
				} elseif($result['Code'] >= 400 && $result['Code'] <= 599) {
					$brokenLinks++;
				}
				
				// If the result is "Bad" (broken), create a BrokenLink record
				if($result['Code'] >= 400 && $result['Code'] <= 599) {
					$brokenLink = new BrokenLink();
					$brokenLink->Link = $result['Link'];
					$brokenLink->Code = $result['Code'];
					$brokenLink->Status = $result['Status'];
					$brokenLink->LinkCheckRunID = $run->ID;
					$brokenLink->PageID = $page->ID;
					$brokenLink->write();
				}
			}
			
			$pagesChecked++;
		}
		
		// Memory cleanup
		unset($pages);
		
		// Mark as done - this is to indicate that the task has completed (for reporting in CMS)
		$run->FinishDate = date('Y-m-d H:i:s');
		$run->IsComplete = 1;
		$run->PagesChecked = $pagesChecked;
		$run->write();
		
		// Find the URL to the LinkCheckAdmin section in the CMS
		$linkcheckAdminLink = Director::absoluteBaseURL() . singleton('LinkCheckAdmin')->Link();
		
		// Count the number of BrokenLink records created for this run
		$runBrokenLinks = ($run->BrokenLinks()->Count()) ? $run->BrokenLinks()->Count() : 0;
		
		if(Director::is_ajax()) {
			return array(
				'Date' => $run->obj('Created')->Nice(),
				'LinkCheckRunID' => $run->ID
			);
		} elseif(Director::is_cli()) {
			echo "SilverStripe Link Checker results\n";
			echo "---------------------------------\n\n";
			
			echo "$pagesChecked pages were checked for broken links.\n";
			echo "$goodLinks links were OK.\n";
			echo "$checkLinks links were redirected.\n";
			echo "$brokenLinks links were broken, and {$runBrokenLinks} BrokenLink records were generated for them.\n\n";
			
			echo "LinkCheckRun ID #{$run->ID} was created with {$runBrokenLinks} BrokenLink related records.\n";
			echo "Please visit $linkcheckAdminLink to see which broken links were found.\n\n";
		} else {
			echo "<h1>SilverStripe Link Checker results</h1>";
			
			echo '<ul>';
			echo "<li>$pagesChecked pages were checked for broken links.</li>";
			echo "<li>$goodLinks links were OK.</li>";
			echo "<li>$checkLinks links were redirected.</li>";
			echo "<li>$brokenLinks links were broken, and {$runBrokenLinks} BrokenLink records were generated for them.</li>";
			echo '</ul>';
			
			echo "<p>LinkCheckRun ID #{$run->ID} was created with {$runBrokenLinks} BrokenLink related records.</p>";
			echo "<p>Please visit <a href=\"$linkcheckAdminLink\">$linkcheckAdminLink</a> to see which broken links were found.</p>";
		}
	}
	
}