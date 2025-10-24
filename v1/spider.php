<a id="dialog-triggle">Back to Main</a>


<?php

$max_number  = $argv[2];
$URL      = $argv[1];
$host     = "localhost";
$username = "root";
$database = "db";
$password = "123456";

// read stopwords file
// https://stackoverflow.com/questions/6159683/read-each-line-of-txt-file-to-new-array-element
$stopwords = explode("\n", file_get_contents('NLTKstopwords.txt'));

// MySQL connector
$conn = mysqli_init( );
mysqli_ssl_set( $conn, NULL, NULL, "DigiCertGlobalRootCA.crt.pem", NULL, NULL );
mysqli_real_connect( $conn, $host, $username, $password, $database, 3306 );
// success flag
$count_query = 0;  // count for number of query


if ( $conn->connect_errno ) {
	die( 'Failed to connect to MySQL: ' . $conn->connect_error );
}

 // Fetch the contents of the web page.
//  echo "<p>python ./spider.py '$URL' $max_number </p>";

$time_start = microtime(true);
$content = shell_exec( " python ./spider.py '$URL' $max_number " );

$obj =  json_decode( "$content" ) ;
$URLs =  $obj->{'URL Table'};  // array(2)
$pageRanks = $obj->{'pr_scores'};

// var_dump( $pageRanks );


foreach ( $URLs as $url_item ) {
	// echo htmlspecialchars( $url_item );
	// insert URL detail into URL table
	$json_item = json_decode( $url_item );
	
		
	$curr_title = $json_item->{'title'};
	$curr_description = $json_item->{'description'};
	$curr_url = $json_item->{'url'};
	$curr_keywords = $json_item->{'keywords'};
	
	// echo strip_tags( $json_item->{'body'} );
	// echo "<p>$curr_bodycontent</p>";

	$results = $conn->query( "select urlID from allurl where url = '$curr_title';" );
	$count_query ++;

	if ( $results->num_rows === 0 ) {
	$sql = <<<SQL
insert into allurl (url, title, keywords, description)
values (
	'$curr_url', 
	'$curr_title',
	'$curr_keywords',
	'$curr_description'
);
SQL;	
	
	echo "<p>$sql</p>";
	$conn->query( $sql );

	// get keeywords splitted be space & put to keywords table
	// 不使用body，MySQL查询有限制
	$curr_content = $curr_title . " " .  $curr_keywords . " " .  $curr_description;
	preg_match_all( '/\b\w+\b/', $curr_content, $matches, PREG_PATTERN_ORDER
);
	
	$results->free();

	// var_dump( $matches[0] );  //  } }
	// SQL for keywords tables
	$KeywordInsertSQL = "insert into keywords (keyword) values ";
	$keywords_insertion = array();

	foreach ( $matches[0] as $match_item ) {
		// remove stowords
		if ( in_array( $match_item, $stopwords) ) {
			// https://www.php.net/manual/en/function.in-array.php
			continue;
		}
		else if ( strlen( $match_item ) <= 1 ) {
			continue;
		}
		
		// echo "<p>$match_item</p>";
		$resultsKW = $conn->query( "select kwID from keywords where keyword = '$match_item';");
		$count_query ++;
		
		if ( $resultsKW->num_rows > 0 ) {
			// keyword already existing
			// echo "<p>keyword already existing</p>";
		}
		else {
			// concate SQL for keyword table
			if ( !in_array( $match_item, $keywords_insertion ) ) {
				// only insert keyword that does not exist
				// avoid duplicate keyword in one page
				$KeywordInsertSQL .= " ( '$match_item' ),";
				$keywords_insertion[] = $match_item;
			}
		}
		

		$resultsKW->free();

	}

	$KeywordInsertSQL = substr_replace( $KeywordInsertSQL, ";", -1);
	

	echo( "<p>$KeywordInsertSQL</p>" );
	$conn->query( $KeywordInsertSQL );	
	$count_query ++;

	$index_SQL = " insert into url_index(kwID, urlID) values ";
	
	foreach ( $keywords_insertion as $keyword_item ) {
			
		$searchkwIDResult = $conn->query( "select kwID from keywords where keyword = '$keyword_item';");
		$count_query ++;
		$searchURLIDResult = $conn->query( "select urlID  from allurl where url = '$curr_url';");
		$count_query ++;

		
		$kwID = $searchkwIDResult->fetch_row()[0];
		$urlID = $searchURLIDResult->fetch_row()[0];

           	
		$index_SQL .= "($kwID, $urlID),";
		

    	$searchkwIDResult->free();
        $searchURLIDResult->free();
	}

	$index_SQL = substr_replace( $index_SQL, ";", -1 );
	
	echo $index_SQL;
        $conn->query( $index_SQL );
	$count_query ++;

		
	}
	else {
		echo "<p>$curr_url already existing</p>";
	}
}


// set Page Ranks
foreach ( $pageRanks as $pageURL => $pageRank ) {
	// echo "<p>$pageURL --> $pageRank</p>";
	$pageRankSetting =<<<PAGERanksValue
	update allurl
	set pagerank=$pageRank
	where url='$pageURL';
	PAGERanksValue;

	echo $pageRankSetting;

	$conn->query( $pageRankSetting );
	$count_query ++;

}	

$time_end = microtime(true);
$execution_time = $time_end - $time_start;

$contentBody =<<<HTML
	<p>There are $count_query queries in total</p>
	<p style='font-size: 36px; font-weight: bold; font-style: italic;'>
		Total Execution Time of the program: <span style='color: #FF0000; font-size: 44px; font-style: italic;'>$execution_time</span> seconds
	</p>
HTML;
echo( $contentBody );


// echo "'$content'";

$backToAdminiPage =<<<HTML
    <dialog id="backToAdmini" >
        <form method="post" action="toAdmin.php">
        <p> <label for="admin-password">Admin Password</label>
            <input type="password" name="admin-password" placeholder="Please enter the admin password" id="admin-password" class="password" required="">
        </P>
        <p>
        <button class="btn" id="backToAdmin" type="submit">To Admin</button>
        </P>
        </form>
    </dialog>
<script src="Dialog.js" defer></script>
HTML;

echo ( $backToAdminiPage  );

$conn->close();
?>
