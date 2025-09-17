/*
CREATE TABLE IF NOT EXISTS keywords (
  kwID     bigint AUTO_INCREMENT PRIMARY KEY,
  keyword  VARCHAR(255) not null
  UNIQUE (keyword)
 );

CREATE TABLE IF NOT EXISTS allurl (
  urlID  bigint AUTO_INCREMENT PRIMARY KEY,
  url    VARCHAR(256) not null UNIQUE,
  title  VARCHAR(256),
  keywords varchar(256),
  description text,
  pagerank double default 0.0
  -- bodycontent text
);

CREATE TABLE IF NOT exists url_index (
  kwID   bigint,
  urlID  bigint,
  PRIMARY KEY ( kwID, urlID ),
  FOREIGN KEY ( kwID  ) REFERENCES  keywords  ( kwID ) ON UPDATE CASCADE,
  FOREIGN KEY ( urlID ) REFERENCES  allurl ( urlID ) ON UPDATE CASCADE
);
*/

<?php

$max_number  = $argv[2];
$URL      = $argv[1];
$host     = "localhost:3306";
$username = "root";
$database = "root";
$password = "123456";

// read stopwords file
// https://stackoverflow.com/questions/6159683/read-each-line-of-txt-file-to-new-array-element
$stopwords = explode("\n", file_get_contents('NLTKstopwords.txt'));

// MySQL connector
$conn = mysqli_init( );
mysqli_ssl_set( $conn, NULL, NULL, "DigiCertGlobalRootCA.crt.pem", NULL, NULL );
mysqli_real_connect( $conn, $host, $username, $password, $database, 3306 );
// success flag
$success_flag = false;  // if operation is succss
$count_total = 0;  // total SQL
$count_fail = 0;  // fail SQL


if ( $conn->connect_errno ) {
	die( 'Failed to connect to MySQL: ' . $conn->connect_error );
}

 // Fetch the contents of the web page.
//  echo "<p>python ./spider.py '$URL' $max_number </p>";

$time_start = microtime(true);
$content = shell_exec( " python ./spider.py '$URL' $max_number " );
$time_end = microtime(true);

$obj =  json_decode( "$content" ) ;
$URLs =  $obj->{'URL Table'};  // array(2)
$pageRanks = $obj->{'pr_scores'};

// var_dump( $pageRanks );

// $word_freq = $obj->{'Word Freq'};  // object(stdClass)#3
$execution_time = $time_end - $time_start;
echo "<p style='font-size: 36px; font-weight: bold; font-style: italic;'>Total Execution Time of Crawling( Python ): <span style='color: #FF0000; font-size: 44px; font-style: italic;'>$execution_time</span> seconds</p>";

// start time of insert table
$insert_start_time =  microtime(true);


foreach ( $URLs as $url_item ) {
	// echo htmlspecialchars( $url_item );
	// insert URL detail into URL table
	$json_item = json_decode( $url_item );
		
	$curr_title = $json_item->{'title'};
	$curr_description = $json_item->{'description'};
	$curr_url = $json_item->{'url'};
	$curr_keywords = $json_item->{'keywords'};
	$curr_bodycontent = strip_tags($json_item->{'body'});
	
	// echo strip_tags( $json_item->{'body'} );
	// echo "<p>$curr_bodycontent</p>";

	$results = $conn->query( "select urlID from allurl where url = '$curr_title';" );
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
	
	
	$success_flag = $conn->query( $sql );
	// increments total SQL and fail SAL by 1
	$count_total++;

	if ( !$success_flag ) {
		$count_fail++;
	}
	

	// get keeywords splitted be space & put to keywords table
	// 不使用body, MySQL查询有限制
	$curr_content = $curr_title . $curr_keywords . $curr_description;
	preg_match_all( '/\b\w+\b/', $curr_content, $matches, PREG_PATTERN_ORDER
);
	$results->free();

	// var_dump( $matches[0] );  //  } }
	
	foreach ( $matches[0] as $match_item ) {
		// remove stowords
		if ( in_array( $match_item, $stopwords) ) {
			// https://www.php.net/manual/en/function.in-array.php
			continue;
		}
		
		// echo "<p>$match_item</p>";
		$results = $conn->query( "select kwID,frequency from keywords where keyword = '$match_item';" );


		if ( $results->num_rows > 0 ) {
			// keyword already existing
			echo "<p>keyword already existing</p>";
		}
		else {
			// no result
			$SQL = <<<SQL
			insert into keywords (keyword)
			values ('$match_item');
			SQL;
			echo $SQL;
			echo "<br>";

			
			$success_flag = $conn->query( $SQL );

			// increments total SQL and fail SAL by 1
                        $count_total++;

                        if ( !$success_flag ) {
                        	$count_fail++;
			}


		}

		$searchkwIDResult = $conn->query( "select kwID from keywords where keyword = '$match_item';");
                $searchURLIDResult = $conn->query( "select urlID  from allurl where url = '$curr_url';");

		$kwID = $searchkwIDResult->fetch_row()[0];
		$urlID = $searchURLIDResult->fetch_row()[0];

		$INSERTSQL = <<<SQL
		insert into url_index(kwID, urlID)
		values($kwID, $urlID);
		SQL;
		
		echo $INSERTSQL;
		$success_flag = $conn->query( $INSERTSQL );

		 // increments total SQL and fail SAL by 1
                 $count_total++;

                 if ( !$success_flag ) {
                 	$count_fail++;
			
		 }

		 $searchkwIDResult->free();
		 $searchURLIDResult->free();

		 $results->free();
	}


	}
	else {
		echo "<p>$curr_url already existing</p>";
	}
}
$end_insert_time =  microtime(true);
$insert_time = $end_insert_time -  $insert_start_time;

// var_dump( $word_freq );
echo "<br>";

$start_pr_time =  microtime(true);
// set Page Ranks
foreach ( $pageRanks as $pageURL => $pageRank ) {
	echo "<p>$pageURL --> $pageRank</p>";
	$pageRankSetting =<<<PAGERanksValue
	update allurl
	set pagerank=$pageRank
	where url='$pageURL';
	PAGERanksValue;

	echo $pageRankSetting;

	$success_flag = $conn->query( $pageRankSetting );

	// increments total SQL and fail SAL by 1
        $count_total++;

        if ( !$success_flag ) {
		$count_fail++;
        }

}	
$end_pr_time =  microtime(true);



$pr_time = $end_pr_time -  $start_pr_time;

echo "<p style='font-size: 36px; font-weight: bold; font-style: italic;' >Total Execution Time of Insersion: <span style='color: #FF0000; font-size: 44px; font-style: italic;'>$insert_time</span> seconds</p>";

echo "<p style='font-size: 36px; font-weight: bold; font-style: italic;'>Total Execution Time of Page Rank: <span style='color: #FF0000; font-size: 44px; font-style: italic;'>$pr_time</span> seconds</p>";


if ( $success_flag &&  $count_fail === 0  ) {
	// all insert or update are success
	$out_html = <<<HTML
	<p>All execution are success</p>
	HTML;
	
	echo $out_html;
}
else {
	$out_html = <<<HTML
	<p>Something wrong with SQL since $success_flag is false</p>
	HTML;
	echo $out_html;
}

echo "<p>There are $count_total SQLs in total</p>";
echo "<p>And fails $count_fail times</p>";

if ( $count_total === $count_fail ) {
	echo( "<p>Success!</p>" );

}
// echo "'$content'";

$backToAdminiPage =<<<HTML
<a id="dialog-triggle">Back to Main</a>
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
