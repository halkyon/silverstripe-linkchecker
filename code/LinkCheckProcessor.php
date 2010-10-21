<?php
/**
 * This class handles taking a URL, taking the HTML that
 * is returned from a URL - then extracting all the href
 * values from <a> elements in the HTML.
 * 
 * Each of those href values is then checked to see what
 * the response code returned is, e.g. "200" or "404" to
 * determine what the status of the link is.
 * 
 * These href values are ignored:
 * 
 * - Anchor links. e.g. "mysite.com/blah#top"
 * - Mailto links. e.g. "mailto:someone@somewhere.com"
 * 
 * @package linkchecker
 */
class LinkCheckProcessor {
	
	/**
	 * The agent name for this process used in HTTP headers.
	 * @var string
	 */
	public static $agent_name = 'SilverStripe Link Checker 0.1';
	
	/**
	 * The current URL being parsed for links. This is
	 * a required parameter for when this class is constructed.
	 *
	 * @var string
	 */
	protected $url;
	
	/**
	 * Supress echo messages if set to false. This is particularly
	 * useful for using this class while performing an ajax request,
	 * as the messages could be detrimental to the operation of the
	 * request.
	 * 
	 * @var boolean
	 */
	public $showMessages = true;
	
	/**
	 * Start a new instance of this class.
	 * @param string $url The URL to parse for links
	 */
	function __construct($url) {
		$this->url = $url;
		
		if(SapphireTest::is_running_test()) $this->showMessages = false;
	}
	
	/**
	 * This method needs to be called after this
	 * class is constructed in order to start the link
	 * checking process, then return a result.
	 * 
	 * @return array Results for each link (link and status code)
	 */
	public function run() {
		$result = array();
		
		// This could take a while to run, so set no time limit
		set_time_limit(0);
		
		// This is used for ob_flush to work
		if(ob_get_length() === false && $this->showMessages) ob_start();
		
		$html = $this->fetchHTML($this->url);

		if(!$html) {
			if($this->showMessages) echo "$this->url doesn't appear to exist.\r\n";
		}

		$links = $this->extractLinks($html, $this->url);
		
		// HTML stored in memory is no longer required, discard it
		unset($html);
	
		if($this->showMessages) echo "Getting HTML from {$this->url}\r\n";

		if(empty($links)) {
			if($this->showMessages) {
				echo "$this->url doesn't appear to have any links.\r\n";
			}
		}
		
		// We only need unique links, so take any duplicates and discard them
		$links = array_values(array_unique($links));
		$linkCount = count($links);
		
		if($this->showMessages) echo "Found {$linkCount} links on {$this->url}\r\n";

		if($this->showMessages) {
			flush();
			ob_flush();
		}

		// Each unique link needs to be checked to see what status code is returned
		for($i = 0; isset($links[$i]) && !connection_aborted(); ++$i) {
				
			// First, we need to check the URL exists, before we can get the status
			if($this->urlExists(html_entity_decode($links[$i]))) {
				// Get the headers for the link
				$headers = $this->fetchHeaders(html_entity_decode($links[$i]));
	
				// Get the status code from the headers
				$status = $this->extractStatusCode($headers);
				
				// Build the results (link, code, status)
				$result[$i]['Link'] = $links[$i];
				$result[$i]['Code'] = $status[0];
				$result[$i]['Status'] = $status[1];
				
			} else {
				// URL doesn't exist, result is a 404.
				$result[$i]['Link'] = $links[$i];
				$result[$i]['Code'] = '404';
				$result[$i]['Status'] = 'Page not found';
			}

			if($this->showMessages) {
				flush();
				ob_flush();
			}
		}

		if($this->showMessages) {
			ob_end_flush();
		}

		return $result;
	}

	/**
	 * Extracts links from the HTML.
	 * 
	 * This does the hard work of extracting all the <a href=""> links
	 * from the HTML, using preg_match_all()
	 *
	 * @param string $html The HTML to extract links from
	 * @param string $url The URL the HTML came from (used to make absolute links)
	 * @return array List of all the links extracted from the HTML
	 */
	public function extractLinks($html, $url) {
		$links = array();
		$url_info = parse_url($url);
		
		preg_match_all("/<a[\s]+[^>]*?href[\s]?=[\s\"\']+(.*?)[\"\']+.*?>/", $html, $matches);
		
		if(empty($url_info['path'])) $url_info['path'] = '/';
		
		// If there is a file at the end of the URL, then get it
		if($url_info['path']{strlen($url_info['path']) - 1} != '/') {
			$url_info['path'] = substr( $url_info['path'], 0, strrpos($url_info['path'], '/') + 1);
		}
		
		if(substr($url_info['host'], 0, 4) == 'www.') $host = substr($url_info['host'], 4) . '/';
		else $host = $url_info['host'];
		
		for($i = 0; isset($matches[1][$i]); $i++) {
			if($matches[1][$i]{0} != '#' && !strpos($matches[1][$i], '@')) { // stop #top sort of links and remove emails
				if(strpos($matches[1][$i], '#')) $matches[1][$i] = substr($matches[1][$i], 0, strpos($matches[1][$i], '#'));
	
				if($matches[1][$i]{0} == '/') { // add host to any links
					$links[] = $url_info['scheme'] . '://' . $url_info['host'] . $matches[1][$i];
				} elseif($matches[1][$i]{0} == '.') {
					$done = true;
					$url = $matches[1][$i];
					$cur_dir = explode('/', $url_info['path']);
					array_shift($cur_dir);
					array_pop($cur_dir);
					
					for($j = 0; isset($cur_dir[$j]); $j++) $cur_dir[$j] = '/' . $cur_dir[$j];
					
					while($done) {
						// if no more ./ or ../ then it's done
						if($url{0} != '.') {
							$links[] = $url_info['scheme'] . '://' . $url_info['host'] . implode('', $cur_dir) . '/' . $url;
							$done = false;
						} elseif(substr($url, 0, 2 ) == './') { // remove same dir as that is the default
							$url = substr($url, 2);
						} else {
							$url = substr($url, 3);
							array_pop($cur_dir);
						}
					}
				} elseif(substr($matches[1][$i] , 0, 7) != 'http://' && substr($matches[1][$i], 0, 8) != 'https://') { // do any links left without root
					/*
					We can't use $url_info['host'].$url_info['path'] since it breaks in the following situations:
					1. in a page (http://localhost/mysite/page1) link as '<a href="page2">Page2</a>', the rendered link becomes http://localhost/mysite/page1/page2.
					2. in a page (http://www.mysite.com/page1) link as '<a href="page2">Page2</a>', the rendered link becomes http://www.mysite.com/page1/page2.
					3. in a page (http://mysite.localhost/page1) link as '<a href="page2">Page2</a>', the rendered link becomes http://mysite.localhost/page1/page2.
					i.e. it is always rendered as a nested url if the current page is not "/", which causes a false alerm in normal case (NestedURL module is not used)				
					$links[] = $url_info['scheme'] . '://' . $url_info['host'] . $url_info['path'] . $matches[1][$i];
					*/
					$links[] = Director::absoluteBaseURL() . $matches[1][$i];
				} else {
					$links[] = $matches[1][$i];
				}	
			}
		}
		
		return $links;
	}
	
	/**
	 * Return HTML content from a URL.
	 * 
	 * @param string $url The URL to get
	 * @return mixed string contents or false
	 */
	public function fetchHTML($url) {
		$contents = '';
		
		ini_set('user_agent', 'User-Agent: ' . self::$agent_name);
		
		if(($fp = @fopen($url, 'r'))) {
			while($data = fread($fp, 1024)) $contents .= $data;
			
			fclose($fp);
		} elseif($url_info = parse_url($url)) {
			$port = isset($url_info['port']) ? $url_info['port'] : 80;
			
			if($url_info['scheme'] == 'https') {
				$fp = fsockopen('ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
			} else {
				$fp = fsockopen($url_info['host'], $port, $errno, $errstr, 30);
			}
			
			if(!$fp) {
				echo "$errstr ($errno)\r\n";
			} else {
				$out = 'HEAD ' . (isset($url_info['path']) ? $url_info['path'] : '/') .
					(isset($url_info['query']) ? '?' . $url_info['query']: '') .
					" HTTP/1.1\r\n";
				
				$out .= 'Host: ' . $url_info['host'] . "\r\n";
				$out .= 'User-Agent: ' . self::$agent_name . "\r\n";
				$out .= "Connection: Close\r\n\r\n";
				
				fwrite($fp, $out);
				
				while(!feof($fp)) $contents .= fgets($fp, 128);
				
				fclose($fp);
				
				list($headers, $content) = explode("\r\n\r\n", $contents, 2);
			}
		}
		
		return $contents;
	}
	
	/**
	 * Check if a given URL actually exists, and can be parsed
	 * to get the headers and content from.
	 *
	 * @param string $url
	 * @return boolean
	 */
	public function urlExists($url) {
		$headers = @get_headers($url);
		return is_array($headers) ? true : false;
	}
	
	/**
	 * Return HTTP headers for a URL given.
	 * 
	 * @param string $url The url to get the headers of
	 * @return mixed string headers or false
	 */
	public function fetchHeaders($url) {
		$headers = '';

		if($url_info = parse_url($url)) {
			$port = (isset($url_info['port'])) ? $url_info['port'] : 80;
			
			if($url_info['scheme'] == 'https') {
				$fp = fsockopen('ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
			} else {
				$fp = fsockopen($url_info['host'], $port, $errno, $errstr, 30);
			}
			
			if(!$fp) {
				echo "$errstr ($errno)\r\n";
			} else {
				$out = 'HEAD ' . (isset($url_info['path']) ? $url_info['path'] : '/') .
					(isset($url_info['query']) ? '?' . $url_info['query']: '') .
					" HTTP/1.1\r\n";
					
				$out .= 'Host: ' . $url_info['host'] . "\r\n";
				$out .= 'User-Agent: ' . self::$agent_name . "\r\n";
				$out .= "Connection: Close\r\n\r\n";
		
				fwrite($fp, $out);

				$contents = '';

				while(!feof($fp)) $contents .= fgets($fp, 128);
				
				fclose($fp);
		
				list($headers, $content) = explode("\r\n\r\n", $contents, 2);
				$headers = explode( "\r\n", $headers );
			}
		}
		
		return $headers;
	}
	
	/**
	 * Pull out the HTTP status code from the headers.
	 * 
	 * @param array $headers List of headers
	 * @return array
	 */
	public function extractStatusCode($headers) {
		foreach($headers as $header) {
			// Checks if the header is the status header
			if(preg_match("/HTTP\/[0-9A-Za-z +]/i", $header)) {
				// If it is save the status
				$status = preg_match("/HTTP\/[0-9]\.[0-9] ([^ ]*) (.*)/i", $header, $matches);
				
				return array($matches[1], $matches[2]);
			}
		}
	}
	
}