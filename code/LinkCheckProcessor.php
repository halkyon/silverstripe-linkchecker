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
class LinkCheckProcessor extends Object {

	/**
	 * The agent name for this process used in HTTP headers.
	 * @var string
	 */
	protected static $agent_name = 'SilverStripe Link Checker 0.1';
	
	/**
	 * Set the agent name used in the HTTP headers, for
	 * when the link checker visits URLs.
	 * 
	 * @param string $name Agent name to be used
	 */
	public static function set_agent_name($name) {
		self::$agent_name = $agent;
	}
	
	/**
	 * The current URL being parsed for links. This is
	 * a required parameter for when this class is constructed.
	 *
	 * @var string
	 */
	protected $url;
	
	/**
	 * Start a new instance of this class.
	 * @param string $url The URL to parse for links
	 */
	function __construct($url) {
		$this->url = $url;
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
		if(ob_get_length() === false) ob_start();
		
		$html = $this->fetchHTML($this->url);

		if(!$html) echo "$this->url doesn't appear to exist.\r\n";

		$links = $this->extractLinks($html, $this->url);
		
		// HTML stored in memory is no longer required, discard it
		unset($html);
	
		echo "Getting HTML from {$this->url}\r\n";

		if(empty($links)) echo "$this->url doesn't appear to have any links.\r\n";
		
		// We only need unique links, so take any duplicates and discard them
		$links = array_values(array_unique($links));
		$linkCount = count($links);
		
		echo "Found {$linkCount} links on {$this->url}\r\n";

		flush();
		ob_flush();

		// Each unique link needs to be checked to see what status code is returned
		for($i = 0; isset($links[$i]) && !connection_aborted(); ++$i) {
				
			// Give server a break after checking each link
			usleep(500000);

			// Get the headers for the link
			$headers = $this->fetchHeaders(html_entity_decode($links[$i]));

			// Get the status code from the headers
			$status = $this->extractStatusCode($headers);

			// Build the results (link, code, status)
			$result['Link'] = $links[$i];
			$result['Code'] = $status[0];
			$result['Status'] = $status[1];

			flush();
			ob_flush();
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
	protected function extractLinks($html, $url) {
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
					$links[] = $url_info['scheme'] . '://' . $url_info['host'] . $url_info['path'] . $matches[1][$i];
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
	protected function fetchHTML($url) {
		$contents = '';
		
		ini_set('user_agent', 'User-Agent: ' . self::$agent_name);
		
		if(($fp = fopen($url, 'r'))) {
			while($data = fread($fp, 1024)) $contents .= $data;
			
			fclose($fp);
		} elseif($url_info = parse_url($url)) {
			if($url_info['scheme'] == 'https') {
				$fp = fsockopen('ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
			} else {
				$fp = fsockopen($url_info['host'], isset($url_info['port']) ? $url_info['port'] : 80, $errno, $errstr, 30);
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
	 * Return HTTP headers for a URL given.
	 * 
	 * @param string $url The url to get the headers of
	 * @return mixed string headers or false
	 */
	protected function fetchHeaders($url) {
		$headers = '';

		if($url_info = parse_url($url)) {
			
			if($url_info['scheme'] == 'https') {
				$fp = fsockopen('ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
			} else {
				$fp = fsockopen($url_info['host'], isset($url_info['port']) ? $url_info['port'] : 80, $errno, $errstr, 30);
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
	protected function extractStatusCode($headers) {
		for($i = 0; isset($headers[$i]); $i++) {
			// Checks if the header is the status header
			if(preg_match("/HTTP\/[0-9A-Za-z +]/i", $headers[$i])) {
				// If it is save the status
				$status = preg_match("/http\/[0-9]\.[0-9] (.*) (.*)/i", $headers[$i], $matches);
				
				return array($matches[1], $matches[2]);
			}
		}
	}	
	
}

?>