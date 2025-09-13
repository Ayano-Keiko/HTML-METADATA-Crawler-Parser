# HTML METADATA Crawler & Parser
The script aims to crawl metadata (eg. title, keywords, description) based on a focused URL. And it is used for SQL insertion or JSON data saving. If IP limitation is ebabled, a Anti-Scraping mechanisam, then we cannot get the metadata. Therefore, commercial wensites are not recommended to test.


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

Finally, PHP file `admin.php` call spider.php
```PHP
system( "/usr/bin/php spider.php '$seed_url' $max_number " );
```

## Get Start
I use form to upload 'GET' data to php, which parses the data and process further.<br />
