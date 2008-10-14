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
	 * These classes (page types) are ignored,
	 * and won't be checked for broken links.
	 * 
	 * @var array
	 */
	protected static $exempt_classes = array(
		'ErrorPage'
	);
	
	/**
	 * Set the classes that should be exempt from
	 * being checked for broken links.
	 *
	 * @param array $classes List of classes to ignore
	 */
	public static function set_exempt_classes($classes) {
		self::$exempt_classes = $classes;
	}
	
	/**
	 * Run the LinkCheckTask.
	 */
	public function process() {
		$goodLinks = 0;   // 200-299 HTTP status codes
		$checkLinks = 0;  // 300-399 HTTP status codes
		$brokenLinks = 0; // 400-599 HTTP status codes
		
		// Get all Page records from the DB
		$pages = DataObject::get('SiteTree');
		
		if($pages) {
			$run = new LinkCheckRun(); // We have started a new run, create the object and write it
			$run->write();

			foreach($pages as $page) {
				if(in_array($page->class, $this->stat('exempt_classes'))) {
					break;
				}
				
				$processor = new LinkCheckProcessor($page->AbsoluteLink());
				$results = $processor->run();
				
				if($results) {
					foreach($results as $result) {
						
						// Iterate the appropriate counter for the result status code
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
				}
			}
			
			// Find the URL to the LinkCheckAdmin section in the CMS
			$linkcheckAdminLink = Director::absoluteBaseURL() . singleton('LinkCheckAdmin')->Link();
			
			// Count the number of BrokenLink records created for this run
			$runBrokenLinks = $run->BrokenLinks()->Count() ? $run->BrokenLinks()->Count() : 0;
			
			echo "SilverStripe Link Checker results\n";
			echo "---------------------------------\n\n";
			
			echo "$goodLinks links were OK.\n";
			echo "$checkLinks links were redirected.\n";
			echo "$brokenLinks links were broken, and {$runBrokenLinks} BrokenLink records were generated for them.\n\n";
			
			echo "LinkCheckRun ID #{$run->ID} was created with {$runBrokenLinks} BrokenLink related records.\n";
			echo "Please visit $linkcheckAdminLink to see which broken links were found.\n\n";
		}
	}
	
}

?>