<?php
	

	function crawl($seed_url, $max_number) {
		 
		 // database info
		 $host = "localhost";
		 $username = "root";
		 $database = "db";
		 $password = "123456";

		 // MySQL connector
		 $conn = mysqli_init( );
		 mysqli_ssl_set( $conn, NULL, NULL, "DigiCertGlobalRootCA.crt.pem", NULL, NULL );
		 mysqli_real_connect( $conn, $host, $username, $password, $database, 3306 );
		 
		 $count_query = 0;  // successful query
		 
		 $urls_visited = array(); // visited urls
		 $urls = array();  // urls to be visited, DFS
		 // at the beginning, add seed url as first url
		 $urls[] = $seed_url;
		 
		 // set page number for count total number
		 $numberPage = 1;
		 // stopwords loading for remove stopwords
		 $stopwords = explode("\n", file_get_contents('NLTKstopwords.txt'));
		 // pages map
		 // { url1 -> [all <a> links], url2 -> [all <a> links], ... } 
		 $pageMaps = array();
		 

		 while ( count($urls) > 0 &&  $numberPage <= $max_number  ) {
		       // if not crawl the url, then starting pop the first iten in url list as current curl and craling current url
		       $current_url = array_shift( $urls );
		       
		       if ( !in_array( $current_url, $urls_visited ) ) {
		       // only crawl url that not in visited list
		       echo( "<p>Start crwaling No. {$numberPage}. " . strip_tags($current_url) . "</p>" );
		       // save current to visited list
		       $urls_visited[] = $current_url;
		       // get HTML source page via lynx
		       $html = shell_exec( "lynx -source $current_url");
		       // match all sub urls
		       preg_match_all( "#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#i", shell_exec( "lynx -dump -listonly  $current_url" ), $subURLs);
		       // var_dump( $subURLs[0] );
		 
		       // echo htmlspecialchars($html);
		       // martch page title
		       preg_match( '/<title.*?>(.*?)<\/title>/mi', $html, $title );
		       // match h1, h2 and p tag
		       preg_match_all('/<(h1|h2|p)[^>]*>(.*?)<\/\1>/is', $html,$hyperImport);
		       	       
		       $title = $title ? $title[1] : '';
		       
		       // matadata retireval
		       $metaData = get_meta_tags( $current_url );
		       $keywords = $metaData['keywords'] ?? '';
		       $description = $metaData['description'] ?? '';

		       // echo $keywords . '<br>' . $description . '<br>';
		       
		       $text = $title . ' ' . $keywords . ' ' . $description;

		       foreach ( $hyperImport[2] as $item ) {
                               
                               $text .= ' ' . trim(strip_tags($item)) . ' ';
                       }
		       
		       
		       preg_match_all('/\p{L}+/u', $text, $matchs);  // '/\p{L}+/u' for unicode | '/\s+/mi' for words
		       // var_dump( $matchs );
		       // echo ("<p>$text</p>");

		       // keywords list to be inserted
		       $kws = array();

		       foreach ( $matchs[0] as $kw ) {
		       	   // echo ("{$matchs[$i]}<br>");
			   if ( in_array( $kw, $stopwords ) ) {
			        continue;
			   }
			   else if ( strlen( $kw ) <= 1 ) {
			   	continue;
			   }
			   else {
			   	if ( !in_array( $kw, $kws ) ) {
				   $kws[] = $kw;
				}
			   }
			}

			// var_dump( $kws );
		       
			// insert url
			$sql = <<<SQL
                                 insert into allurl (url, title, keywords, description)
                                 values (
                                 '$current_url',
                                 '
                                 SQL . addslashes(substr( $title, 0, 255 ) ) . <<< SQL
                                 ', '
                                 SQL . addslashes(substr( $keywords, 0, 255 ) ) . <<< SQL
                                 ', '
                                 SQL . addslashes($description) . <<< SQL
                                 ');
                                 SQL;
				 echo ("<p><code>{$sql}</code></p>");

				 // set url key if not exists
				 if ( !array_key_exists( $current_url, $pageMaps ) ) {
				    $pageMaps[$current_url] = [];
				 }
				 
				 
				 // insert into table
				 if ( $conn->query( $sql ) ) {
				      $count_query ++;  
				 }
				 else {
				      echo ( $conn->error );
				 }
				 
				 if (!empty($kws)) {
				     // keywords
				     $valuesKW = array_map(function($item) {
				     return "('" . addslashes($item) . "')";
				     }, $kws);
				     $KeywordInsertSQL = "INSERT IGNORE INTO keywords (keyword) VALUES " . implode(",", $valuesKW);
				     echo( "<p><code>$KeywordInsertSQL</code></p>" );

				     
				     // insert into table
				     if ( $conn->query( $KeywordInsertSQL ) ){
				     	$count_query ++;
				     }
				     else {
                                     	  echo ( $conn->error );
                                     }

				     // url index
				     $valuesUI = array_map(function($item) {
				     return "'" . addslashes($item) . "'";
				     }, $kws);
				     $inClause = implode(",", $valuesUI);
				     $index_SQL = <<<SQL
                                     INSERT IGNORE INTO url_index(kwID, urlID)
                                     SELECT k.kwID, u.urlID
                                     FROM keywords k
                                     JOIN allurl u ON u.url = '$current_url'
                                     WHERE k.keyword IN ($inClause)
                                     SQL;
				     echo ( "<p><code>$index_SQL</code></p>" );

				     // insert into table
				     if ( $conn->query( $index_SQL ) ) {
				     	$count_query ++;
				     }
				     else {
                                          echo ( $conn->error );
                                     }
				     
				 }
				 
				 // extract urls
				  if ( !array_key_exists( $current_url, $pageMaps ) ) {
				     $pageMaps[$current_url] = array();
				  }

				  $normalizedSeed = normalize_domain($seed_url);
				  $filtered = array_filter( $subURLs[0], function($url) use ($normalizedSeed) {
				  // Normalize potential relative "./" paths, etc.
				  $normalizedUrl = normalize_domain($url);
				  
				  // Keep only URLs that start with the seed
				  return strpos($normalizedUrl, $normalizedSeed . '/') === 0;
				  });
				  
				 foreach ( $filtered as $subURL ) {
				     					 
                               	     if ( !in_array( $subURL, $urls_visited ) ) {
				     	 if ( !in_array( $subURL, $urls ) && strlen( $subURL ) !== 0 ) {
					    $urls[] = $subURL;
					    $pageMaps[$current_url][] = $subURL;
					 }
                                         
                                     }
                       	         }
				 
				 $numberPage ++;
		       
		       
		 }
		 else {
		      echo ("<p>" . strip_tags($current_url) . " already crawled!</p>" );
		 }
		 
		 }

		 $numberPage --;
		 echo ( "<p>Finish! Crawl $numberPage pages in total</p>");

		 return array( $count_query, $pageMaps );
	}

	function normalize_domain($url) {
    // Ensure it starts with scheme
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    $parts = parse_url($url);
    if (!$parts) return '';

    $scheme = $parts['scheme'] ?? 'https';
    $host = $parts['host'] ?? '';

    // Remove leading www.
    $host = preg_replace('/^www\./i', '', $host);

    // Rebuild normalized base (only host-level)
    $base = $scheme . '://' . $host;

    // Add path if exists
    if (!empty($parts['path'])) {
        $base .= $parts['path'];
    }
    if (!empty($parts['query'])) {
        $base .= '?' . $parts['query'];
    }

    return rtrim($base, '/');
}

?>
