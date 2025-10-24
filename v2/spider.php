<!--
CREATE TABLE IF NOT EXISTS keywords (
  kwID     bigint AUTO_INCREMENT PRIMARY KEY,
  keyword  VARCHAR(255) not null,
  UNIQUE (keyword)
 );
 
CREATE TABLE IF NOT EXISTS allurl (
  urlID  bigint AUTO_INCREMENT PRIMARY KEY,
  url    VARCHAR(256) not null UNIQUE,
  title  VARCHAR(256),
  keywords varchar(256),
  description text,
  pagerank double default 0.0
  -- bodycontent text  -- 不保存，太大，MYSQL查询有限制，无法太多语句
);
 
CREATE TABLE IF NOT exists url_index (
  kwID   bigint,
  urlID  bigint,
  PRIMARY KEY ( kwID, urlID ),
  FOREIGN KEY ( kwID  ) REFERENCES  keywords  ( kwID ) ON UPDATE CASCADE,
  FOREIGN KEY ( urlID ) REFERENCES  allurl ( urlID ) ON UPDATE CASCADE
);
-->

<link href="../css/Dialog.css" rel="stylesheet" />

<a id="dialog-triggle">Back to Main</a>

<?php

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
?>

<?php

include 'crawl.php';
include 'page-rank.php';


$URL = $argv[1];
$max_number = $argv[2];


$time_start = microtime(true);

$item = crawl($URL, $max_number);
$count_query = $item[0];
$pagesMap = $item[1];

// file_put_contents( "./data/Page-Rank.json", json_encode( $pagesMap ) );

$count_query = $count_query + page_rank( $pagesMap );

$time_end = microtime(true);
$execution_time = $time_end - $time_start;

$contentBody =<<<HTML
	<p>There are $count_query queries in total</p>
	<p style='font-size: 36px; font-weight: bold; font-style: italic;'>
		Total Execution Time of the program: <span style='color: #FF0000; font-size: 44px; font-style: italic;'>$execution_time</span> seconds
	</p>
HTML;
echo( $contentBody );

?>
