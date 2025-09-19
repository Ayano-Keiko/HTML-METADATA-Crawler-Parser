# HTML METADATA Crawler & Parser
The script aims to crawl metadata (eg. title, keywords, description) based on a focused URL. And it is used for SQL insertion or JSON data saving. If IP limitation is ebabled, a Anti-Scraping mechanisam, then we cannot get the metadata. Therefore, commercial wensites are not recommended to test.


## System Requirements
- RHEL 9 ( Red Hat Enterprise Linux release 9.6 (Plow) )
- PHP 8.0.30 (cli) (built: Apr 28 2025 09:46:47)
- Python 3.9.21
- Apache/2.4.62 (Red Hat Enterprise Linux)


## Feature
- No 3<sup>rd</sup> library used. All use python build-in module
- JSON used for returning data to PHP -- Easier for processing


## Description
This script takes commend line argument as parameters. There are 3 parameters. The second parament is seed URL and third parameter is number of max page. And JSON data are returned. <br>

The JSON structure is listed below.<br>
```
    {"URL Table": [url1(dict), url2, ...], "pr_scores": {{'url1': pagerank1, ...}}, "number": count}
```

In `spider.php`, PHP is used to receive JSON data from python execution.<br>
```php
$time_start = microtime(true);
$content = shell_exec( " python ./spider.py '$URL' $max_number " );
$time_end = microtime(true);
```


## Get Start
I use form to upload 'POST' data to php, which parses the data and process further.<br />
For example, use the following code to call spider.php
```php
// some code
$seed_url = trim( $_POST['seed'] );
$max_number = intval( $_POST['max_page'] );
$submit = $_POST['submit'];


if ( $submit === 'Start Crawling') {
	// $command = "mysql --host=$host --user=$username --password=$password --database= $database";
	// $html = shell_exec( "python spider.py $seed_url $max_number " );
	system( "/usr/bin/php spider.php '$seed_url' $max_number " );			
}
// some code
```
