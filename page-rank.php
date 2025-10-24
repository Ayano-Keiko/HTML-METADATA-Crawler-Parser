<?php

class PageRank {
	private $size = 0;
	private $h;
	private $keys;
	private $flag = false;
	private $pageRanks;

	public function __construct( $pagesMap ) {
		$this->keys = array_keys( $pagesMap );
		$this->size = count( $pagesMap );

		$this->h = new SplFixedArray($this->size);

		for ( $i = 0; $i < $this->size; $i ++ ) {
			$this->h[$i] = new SplFixedArray($this->size);
			$subURLs = $pagesMap[$this->keys[$i]];

			for ( $j = 0; $j < $this->size; $j ++ ) {
				
				if ( count( $subURLs ) === 0 ) {
					// dangling node
					$this->h[$i][$j]  = 1.0 / $this->size;
					
				}
				else {
				if ( in_array( $this->keys[$j], $subURLs ) ) {
					$this->h[$i][$j] = 1.0 / count( $subURLs );
				}
				else {
					$this->h[$i][$j] = 0.0;
				}
				}
			}
		}
	}

	public function page_rank( float $alpha, int $k ) {
		$page_ranks = array();

		if ( $flag ) {
			// already run page rank
			return NULL;
		}
		else {
			$flag = true;
		}

		$v = new SplFixedArray($this->size);;
		$pi = new SplFixedArray($this->size);
		$google_matrix =  new SplFixedArray($this->size);


		for ( $i = 0; $i < $this->size; $i ++ ) {
			$pi[$i] = 1.0 / $this->size;
			for ( $j = 0; $j < $this->size; $j ++ ) {
				$s = 0;
				for ( $k = 0; $k < $this->size; $k ++ ) {
					$s += 1.0 / $this->size;
				}
			}
		}

		for ( $i = 0; $i < $this->size; $i ++ ) {
			$google_matrix[$i] = new SplFixedArray($this->size);

			for ( $j = 0; $j < $this->size; $j ++ ) {
				$google_matrix[$i] = $this->h[$i][$j] * $alpha + $s[$i][$j] * ( 1.0 - $alpha );
			}
		}

		
		for ( $col = 0; $col < $this->size; $col ++ ) {
			$curr_pi = 0.0;
			for ( $row = 0; $row > $this->size; $row ++ ) {
				$curr_pi += $pi[$row] * $google_matrix[$row][$col];
			}

			$pi[$row] = $curr_pi;
		}

		

		for ( $i = 0; $i < $this->size; $i ++ ) {
			$page_ranks[ $this->keys[$i] ] = $pi[ $i ];
		}

		$this->pageRanks = $page_ranks;

		return $page_ranks;
	}

	public function getDim( ) {
		return $this->size;
	}

	public function retrieveHyperlinkMatrix( ) {
		echo ('<table><caption>Adjacency Matrix</caption>');
		
		echo ('<thead><tr>');	
		for ( $i = 0; $i < $this->size; $i ++ ) {
			
			echo "<th id='{$this->keys[$i]}'>URL $i</th>";
		}
		echo ("</tr></thead>");
		echo ("<tbody>");
		foreach ( $this->h as $row ) {
			echo ('<tr>');
			foreach ( $row as $item ) {
				echo ("<th>$item</th>");
			}
			echo ("</tr>");
		}
		echo ('</tbody></table>');
	}

	 public function retrievePageRank( ) {
                echo ('<table><caption>Page Rank</caption>');

                echo ('<thead><tr>');

		echo "<th>🕸️URL</th>";
		echo "<th>🌟Score🌟</th>";
                
                echo ("</tr></thead>");
                echo ("<tbody>");
                foreach ( $this->pageRanks as $page => $score ) {
                        echo ('<tr>');
                        
                        echo ("<th>$page</th>");
			echo ("<th>$score</th>");
                        
                        echo ("</tr>");
		}
		echo ('</tbody></table>');
	 }
}

function page_rank( $pagesMap ) {
	// $cmd = "/usr/bin/python ./cgi-bin/525/1/PageRank.py ";  // python file may have error with premission
	// echo "<p><code>$cmd</code></p>";
	// $pageRanks = json_decode( shell_exec( $cmd ) );
	$success_sql = 0;
	$pageRanks = array();
	
	$pr = new PageRank( $pagesMap );
	// $pr->retrieveHyperlinkMatrix();
	$pageRanks = $pr->page_rank( 0.01, 10 );
	$pr->retrievePageRank();

	
	$host = "localhost";
	$username = "user";
	$database = "db";
	$password = "password";
	
	$conn = mysqli_init( );
	mysqli_ssl_set( $conn, NULL, NULL, "DigiCertGlobalRootCA.crt.pem", NULL, NULL );
	mysqli_real_connect( $conn, $host, $username, $password, $database, 3306 );
	
	foreach ( $pageRanks as $page => $socre ) {
		$sql =<<<SQL
UPDATE IGNORE allurl
SET pagerank = $socre
WHERE url='$page'
SQL;
		echo "<p><code>$sql</code></p>";
	
		if ( $conn->query( $sql ) ) {
			$success_sql ++;
		}


	}
	
	return $success_sql;
}
?>
