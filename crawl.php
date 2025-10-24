<?php

	function crawl($seed_url, $max_number) {
		 $host     = "localhost";
		 $username = "user";
		 $database = "db";
		 $password = "password";

		 // MySQL connector
		 $conn = mysqli_init( );
		 mysqli_ssl_set( $conn, NULL, NULL, "DigiCertGlobalRootCA.crt.pem", NULL, NULL );
		 mysqli_real_connect( $conn, $host, $username, $password, $database, 3306 );
		 
		 $count_query = 0;  // successful query
		 
		 $urls_visited = array();
		 $urls = array();
		 $urls[] = $seed_url;
		 $numberPage = 1;
		 // stopwords
		 $stopwords = explode("\n", file_get_contents('NLTKstopwords.txt'));
		 // pages map
		 $pageMaps = array();
		 

		 while ( count($urls) > 0 &&  $numberPage <= $max_number  ) {
		       $current_url = array_shift( $urls );
		       

		       if ( !in_array( $current_url, $urls_visited ) ) {
		       
		       echo( "<p>Start crwaling No. {$numberPage}. " . strip_tags($current_url) . "</p>" );
		       $cmd = "python ./cgi-bin/parserHTML.py '$current_url' '$seed_url'";
		       // echo ( "<p><code>" . strip_tags($cmd) . "</code></p>" );
		       
		       $results = json_decode( shell_exec( $cmd ), true  );
		       
		       $urls_visited[] = $current_url;

		       
		       if ( $results['code'] === 200 ) { 
		       	    	 
		       		 $title = isset( $results['title'] ) ? $results['title'] :  '';
				 $keywords = isset( $results['meta data']['keywords'] ) ? $results['meta data']['keywords'] : '';
				 $description = isset( $results['meta data']['description'] ) ? $results['meta data']['description'] : '';
				 $importance_sentence = isset( $results['important sentence'] ) ? $results['important sentence'] : '';
				 $subURLs = $results['links'];

				 
				 $text = $title . ' ' . $keywords . ' ' . $description . ' ' . $importance_sentence;
				 preg_match_all('/\p{L}+/u', strip_tags( $text ), $matchKeywords);  // '/\p{L}+/u' for unicode | '/\s+/mi' for words
				 $allKeywords = array();

				 foreach ( $matchKeywords[0] as $kw ) {
				 	 if ( in_array( strtolower( $kw ), $stopwords ) ) {
					      continue;
					 }
					 else if ( strlen( $kw ) <= 1 || is_numeric( $kw ) ) {
					      continue;
					 }
					 else if ( !in_array( $kw, $allKeywords ) ) {
					      $allKeywords[] = $kw;
					      
					 }
					 
				 }

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
				 

				 if (!empty($allKeywords)) {
				     // keywords
				     $valuesKW = array_map(function($item) {
				     return "('" . addslashes($item) . "')";
				     }, $allKeywords);
				     $KeywordInsertSQL = "INSERT IGNORE INTO keywords (keyword) VALUES " . implode(",", $valuesKW);
				     // echo( "<p><code>$KeywordInsertSQL</code></p>" );

				     
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
				     }, $allKeywords);
				     $inClause = implode(",", $valuesUI);
				     $index_SQL = <<<SQL
                                     INSERT IGNORE INTO url_index(kwID, urlID)
                                     SELECT k.kwID, u.urlID
                                     FROM keywords k
                                     JOIN allurl u ON u.url = '$current_url'
                                     WHERE k.keyword IN ($inClause)
                                     SQL;
				     // echo ( "<p><code>$index_SQL</code></p>" );

				     // insert into table
				     if ( $conn->query( $index_SQL ) ) {
				     	$count_query ++;
				     }
				     else {
                                          echo ( $conn->error );
                                     }
				     
				 }


				 
				 
				 // extract urls

				 foreach ( $subURLs as $subURL ) {
				     if ( array_key_exists( $current_url, $pageMaps ) ) {
                                              $pageMaps[$current_url][] = $subURL;
                                     }
					 
                               	     if ( !in_array( $subURL, $urls_visited ) ) {
				     	 $urls[] = $subURL;
                                         
                                     }
                       	         }
				 
				 $numberPage ++;
		       }
		       else {
		       	 //  "code": 404, "message": f"{str(e)}"
			 
			 // echo ( "<p>error code: {$results['code']} info: {$results['message']}</p>" );
		       }
		       
		 }
		 else {
		      echo ("<p>" . strip_tags($current_url) . " already crawled!</p>" );
		 }
		 
		 }

		 $numberPage --;
		 echo ( "<p>Finish! Crawl $numberPage pages in total</p>");
		 
		 return array( $count_query, $pageMaps );
	}

?>
