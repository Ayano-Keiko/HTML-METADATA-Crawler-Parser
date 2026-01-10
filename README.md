# HTML METADATA Crawler & Parser

The script aims to crawl metadata (eg. title, keywords, description) based on a focused URL. And it is used for SQL insertion or JSON data saving. If IP limitation is ebabled, a Anti-Scraping mechanisam, then we cannot get the metadata. Therefore, commercial wensites are not recommended to test. Only English word tokenization supported.


## Development Requirements

https://undcemcs01.und.edu/~wen.chen.hu/course/525/exercise/1/


## Feature

- No CGI/needed, everything in PHP


## Get Start

I use form to upload 'POST' data to php, which parses the data and process further.<br />
For example, use the following code to call spider.php


```php
<!-- admin.html -->
<h1>Focused Web Search Engine -- Admini</h1>
<form action="" method="post" target="_self" autocomplete="off">
    <label for="seed">Seed URL: </label>
    <input type="url" name="seed" id="seed" required />
    <br>
    <label for="max_page">Max page: </label>
    <input type="number" name="max_page" id="max_page" min="0" max="500" required />
    <br>
    <hr>
    <br>
    <button type="submit" name="submit" value="Start Crawling" id="start_crawling">Start Crawling</button>
    <br>
    <button type="submit" name="submit" value="List Indexes" id="list_index">List Indexes</button>
    <br>

    <button type="submit" name="submit" value="Clear System" id="clear_system">Clear System</button>
    <br />
    <button type="button" name="submit" value="List Code" id="list_code" onclick="ListCode()">List Code</button>
    <br />
    <button type="reset" name="reset" value="Reset" id="reset">Reset</button>
    <br />
    <button type="button" name="submit" value="Main" id="main" onclick="backToMain()">Main</button>
</form>

// admin.php
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

> Rust page rank usage
If using Rust Page rank, please create a cargo workspace with ` Cargo.toml ` including below contents

```
[workspace]
members = [
    "main",
    "page-rank"
]
```

And create "main" binary crate and "page-rank" library crate. Replace all things in "page-rank/src" with " page-rank " folders in this repo. Then modify "Cargo.toml" in main crate ` page-rank = { path = "../page-rank" } `. Then compile the project.