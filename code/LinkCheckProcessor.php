<?php

class LinkCheckProcessor extends Object {

	/**
	 * The agent name for this process, used
	 * in the HTTP headers.
	 *
	 * @var string
	 */
	protected static $agent_name = 'SilverStripe LinkChecker 0.1';
	
	/**
	 * The current URL being parsed for links.
	 *
	 * @var string
	 */
	protected $url;
	
	/**
	 * Start a new instance of LinkcheckTask.
	 *
	 * @param string $url The URL to parse for links
	 */
	function __construct($url) {
		$this->url = $url;
		
		$this->process();
	}
	
	/**
	 * This is the default method that is run
	 * whenever this task is invoked.
	 */
	function process() {
		$missing_links = 0;
		$check_links = 0;
		$good_links = 0;

		// This could take a while to run, so set no time limit
		set_time_limit(0);
		
		/* This is used for ob_flush to work */
		if(ob_get_length() === false) ob_start();
		
		// Get the HTML from the URL
		$html = $this->fetchHTML($this->url);

		// If no HTML returned, link doesn't exist, so show error
		if(!$html) return "$this->url doesn't appear to exist.\r\n";

		// Extract all the links from the HTML
		$links = $this->extractLinks($html, $this->url);
		
		// HTML stored in memory is no longer required
		unset($html);
	
		echo "Getting HTML from {$this->url}\r\n";
	
		if(empty($links)) return "$this->url doesn't appear to have any links.\r\n";
		
		// We only need unique links, so take any duplicates and discard them
		$links = array_values(array_unique($links));
		$linkCount = count($links);
		
		echo "Found {$linkCount} links on {$this->url}\r\n";

		flush();
		ob_flush();

		// Each unique link needs to be checked to see what status code is returned
		for($i = 0; isset($links[$i]) && !connection_aborted(); ++$i) {
				
			/* give server a break after checking each link */
			usleep(500000);
				
			$headers = $this->fetchHeaders(html_entity_decode($links[$i]));

			// If available, get the status code from the headers directly
			if(!empty($headers['http_code'])) {
				$status = $headers['http_code'];
			} else {
				// Conventional method is to extract it from the text
				$status = $this->extractStatusCode($headers);
			}
				
			var_dump($status);
			
			// Flush the output buffer
			flush();
			ob_flush();
		}
	}

	/**
	 * Extracts links from HTML passed in.
	 *
	 * @param string $html The HTML to extract links from
	 * @param string $url The URL the HTML came from (used to make absolute links)
	 * @return array List of all the links extracted from the HTML
	 */
	function extractLinks($html, $url) {
	    $links    = array();
	    $url_info = parse_url( $url );
	
	    preg_match_all( "/<a[\s]+[^>]*?href[\s]?=[\s\"\']+(.*?)[\"\']+.*?>/", $html, $matches );
	
	    if( empty( $url_info['path'] ) )
	        $url_info['path'] = '/';
	
	    /* if there is a file at the end of the url get it */
	    if( $url_info['path']{strlen( $url_info['path'] ) - 1} != '/' )
	        $url_info['path'] = substr( $url_info['path'], 0, strrpos( $url_info['path'], '/' ) + 1 );
	
	    if( substr( $url_info['host'], 0, 4 ) == 'www.' )
	        $host = substr( $url_info['host'], 4 ) . '/';
	    else
	        $host = $url_info['host'];
	
	    for( $i=0; isset( $matches[1][$i] ); $i++ )
	    {
	        if( $matches[1][$i]{0} != '#' && ! strpos( $matches[1][$i], '@' ) ) // stop #top sort of links and remove emails
	        {
	
	            if( strpos( $matches[1][$i], '#' ) )
	                $matches[1][$i] = substr( $matches[1][$i], 0, strpos( $matches[1][$i], '#' ) );
	
	            if( $matches[1][$i]{0} == '/' ) // add host to any links
	                $links[] = $url_info['scheme'] . '://' . $url_info['host'] . $matches[1][$i];
	            elseif( $matches[1][$i]{0} == '.' )
	            {
	                $done    = true;
	                $url     = $matches[1][$i];
	                $cur_dir = explode( '/', $url_info['path'] );
	                array_shift($cur_dir );
	                array_pop($cur_dir );
	
	                for( $j=0; isset( $cur_dir[$j] ); $j++ )
	                    $cur_dir[$j] = '/' . $cur_dir[$j];
	
	                for(;$done;)
	                {
	                    /*  if no more ./ or ../ then it's done */
	                    if( $url{0} != '.' )
	                    {
	                        $links[] = $url_info['scheme'] . '://' . $url_info['host'] . implode( '', $cur_dir ) . '/' . $url;
	                        $done    = false;
	                    }
	                    /* remove same dir as that is the default */
	                    elseif( substr( $url, 0, 2 ) == './' )
	                        $url = substr( $url, 2 );
	                    else
	                    {
	                        $url = substr( $url, 3 );
	                        array_pop( $cur_dir );
	                    }
	                }
	            }
	            elseif( substr( $matches[1][$i] , 0, 7 ) != 'http://' && substr( $matches[1][$i] , 0, 8 ) != 'https://' ) // do any links left without root
	                $links[] = $url_info['scheme'] . '://' . $url_info['host'] . $url_info['path'] . $matches[1][$i];
	            else
	            {
					$links[] = $matches[1][$i];
	            }
	        }
	    }
	
	    return $links;
	}
	
	/**
	 * Get information on a URL.
	 *
	 * @param string $url The URL to get
	 * @return mixed string contents or false
	 */
	function fetchHTML($url) {
		$contents = '';
		
		ini_set('user_agent', 'User-Agent: ' . self::$agent_name);
		
		if(($fp = fopen($url, 'r'))) {
			for(;($data = fread($fp, 1024));) $contents .= $data;
			fclose($fp);
		} elseif(function_exists('curl_init')) {
			$ch = curl_init($url);
			
			$options = array(
				CURLOPT_HEADER => false,
				CURLOPT_USERAGENT => self::$agent_name,
				CURLOPT_RETURNTRANSFER => true
			);
			
			curl_setopt_array($ch, $options);

			$contents .= curl_exec($ch);
			
			curl_close($ch);
		} elseif(($url_info = parse_url($url))) {
			if($url_info['scheme'] == 'https') {
				$fp = fsockopen('ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
			} else {
				$fp = fsockopen($url_info['host'], 80, $errno, $errstr, 30);
			}
			
			if(!$fp) return false;
			
			$out = 'HEAD ' . (isset($url_info['path']) ? $url_info['path'] : '/') .
				(isset($url_info['query']) ? '?' . $url_info['query']: '') .
				" HTTP/1.0\r\n";
			
			$out .= 'Host: ' . $url_info['host'] . "\r\n";
			$out .= 'User-Agent: ' . self::$agent_name . "\r\n";
			$out .= "Connection: Close\r\n\r\n";
			
			fwrite($fp, $out);
			
			for(;!feof($fp);) $contents .= fgets($fp, 128);
			
			list($headers, $content) = explode("\r\n\r\n", $contents, 2);
		}
		
		return $contents;
	}
	
	/**
	 * Get headers from a URL.
	 *
	 * @param string $url The url to get the headers of
	 * @return mixed string headers or false
	 */
	function fetchHeaders($url) {
		$headers = '';

		if(function_exists('curl_init')) {
			$ch = curl_init($url);

			$options = array(
				CURLOPT_HEADER => false,
				CURLOPT_NOBODY => true,
				CURLOPT_FOLLOWLOCATION => true,
				CURLOPT_USERAGENT => self::$agent_name,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_ENCODING => ''
			);
			
			curl_setopt_array($ch, $options);
			curl_exec($ch);
			
			$headers = curl_getinfo($ch);
			
			curl_close($ch);
		} elseif(($url_info = parse_url($url))) {
			if($url_info['scheme'] == 'https') {
				$fp = fsockopen('ssl://' . $url_info['host'], 443, $errno, $errstr, 30);
			} else {
				$fp = fsockopen($url_info['host'], 80, $errno, $errstr, 30);
			}
	
			if(!$fp) return false;
	
			$out = 'HEAD ' . (isset($url_info['path']) ? $url_info['path'] : '/') .
				(isset($url_info['query']) ? '?' . $url_info['query']: '') .
				" HTTP/1.0\r\n";
				
			$out .= 'Host: ' . $url_info['host'] . "\r\n";
			$out .= 'User-Agent: ' . self::$agent_name . "\r\n";
			$out .= "Connection: Close\r\n\r\n";
	
			fwrite($fp, $out);
			
			$contents = '';
			
			for(;!feof($fp);) $contents .= fgets($fp, 128);
	
			list($headers, $content) = explode("\r\n\r\n", $contents, 2);
			$headers = explode( "\r\n", $headers );
		}
		
		return $headers;
	}
	
	/**
	 * Pull out the HTTP status code from the headers.
	 *
	 * @param array $headers List of headers
	 * @return array
	 */
	function extractStatusCode($headers) {
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