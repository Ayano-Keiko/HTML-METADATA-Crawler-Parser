# HTML METADATA Crawler & Parser
The script aims to crawl metadata (eg. title, keywords, description) based on a focused URL. And it is used for SQL insertion or JSON data saving. If IP limitation is ebabled, a Anti-Scraping mechanisam, then we cannot get the metadata. Therefore, commercial wensites are not recommended to test. Only support English word tokenization.


## System Environment
The script run on following environment<br>
- RHEL 9 ( Red Hat Enterprise Linux release 9.6 (Plow) )
- PHP 8.0.30 (cli) (built: Apr 28 2025 09:46:47)
- Apache/2.4.62 (Red Hat Enterprise Linux)


## Feature
- JSON used for returning data to PHP -- Easier for processing
- No CGI/Python needed, everything in PHP


## Description
This script takes commend line argument as parameters. There are 3 parameters. The second parament is seed URL and third parameter is number of max page. And JSON data are returned. <br>

The JSON structure is listed below.<br>
```
    {'code': 200, 'title': self.title, 'meta data': self.metaData, 'important sentence': ' '.join(self.importance), 'links': list( self.links ) }
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
