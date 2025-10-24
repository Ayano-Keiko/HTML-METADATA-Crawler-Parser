import sys
import json
import re
import urllib
import urllib.request
import urllib.parse
from html.parser import HTMLParser
import json
import os

class PageParser(HTMLParser):
    def __init__(self, searchURL, seed_url, convert_charrefs = True):
        super().__init__(convert_charrefs=convert_charrefs)
        self.metaData = {}
        self.title = ''
        
        self.searchURL = searchURL
        self.seed_url = seed_url
        
        self.inHead = False
        self.inTitle = False
        self.inScript = False

        self.important_tags = ['h1', 'h2', 'p']
        self.inImportantTas = False
        self.importance = []

        self.links = set()
        self.media_types = { '.jpg', '.jpeg', '.png', '.gif', ".mp3", ".mpeg", '.webp', '.mp4', '.mov', '.avi', '.wav' }

        self.base_domain = urllib.parse.urlparse( seed_url ).netloc
        # print( self.base_domain )
        # print( urllib.parse.urlparse( seed_url ).path )

    def handle_data(self, data):
        if self.inTitle:
            self.title = data
        elif self.inImportantTas:
            self.importance.append( data )
        
    
    def handle_starttag(self, tag, attrs):
        attr_dict = dict(attrs)

        if tag == 'head':
            self.inHead = True
        elif self.inHead and tag == 'meta':
            name = attr_dict.get('name', '')
            content = attr_dict.get('content', '')
            if name and content:
                self.metaData[name.lower()] = content
        elif self.inHead and tag == 'title':
            self.inTitle = True
        elif tag == 'script':
            self.inScript = True
        elif tag in self.important_tags:
            self.inImportantTas = True
        elif tag in { 'a'}:
            link = attr_dict.get( 'href', '' )
            # retrieve 所有相对url pages
            link_domin = urllib.parse.urlparse( link ).netloc
            
            if not link_domin and link:
                
                if link.startswith('..'):
                    # 上级目录里的pages，skip
                    pass
                elif link.endswith('/'):
                    # */ z 类型page
                    self.links.add( urllib.parse.urljoin(url, link) )
                elif os.path.splitext(link)[1] in self.media_types:
                    # media resources --> skip
                    pass
                elif link == '.':
                    pass
                elif re.match(r'[^@]+@[^@]+\.[^@]+', link):
                    # skip the email address
                    pass
                elif link[-1].isalnum():
                    self.links.add(urllib.parse.urljoin(url, link))
                else:
                    pass
            
            else:
                seed_parsed = urllib.parse.urlparse(self.seed_url)
                link_parsed = urllib.parse.urlparse(link)

                # Normalize domain by removing 'www.'
                seed_host = seed_parsed.netloc.replace('www.', '')
                link_host = link_parsed.netloc.replace('www.', '')
                
                if seed_host == link_host and link_parsed.path.startswith(seed_parsed.path):
                    # link.startswith( self.seed_url )
                    # self.seed_url
                    # urllib.parse.urlparse(self.seed_url).netloc
                    # only add if it's the same site
                    self.links.add(link)
                
                    
                            
    def handle_endtag(self, tag):
        if tag == 'head':
            self.inHead = False
        elif tag == 'title':
            self.inTitle = False
        elif tag == 'script':
            self.inScript = False
        elif tag in self.important_tags:
            self.inImportantTas = False

    def retrieveResults(self):
        return {'code': 200, 'title': self.title, 'meta data': self.metaData, 'important sentence': ' '.join(self.importance), 'links': list( self.links ) } # 'body': self.bodyText}


def normalize_url(url):
    parsed = urlparse(url)
    netloc = parsed.netloc.replace('www.', '')
    path = parsed.path.rstrip('/')
    return urlunparse((parsed.scheme, netloc, path, '', '', ''))


    
if __name__ == '__main__':
    headers = {
        'user-agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'
    }
    
    try:
        url = sys.argv[1]
        seed_url = sys.argv[2]

        rep = urllib.request.Request(url=url, headers=headers)
        req = urllib.request.urlopen(rep, timeout=10)

        parser = PageParser( searchURL=url, seed_url=seed_url )
        parser.feed( req.read().decode('UTF-8') )
        results = parser.retrieveResults()
        print(json.dumps(results))
        
    except UnicodeEncodeError as e:
        print( { "code": 600, "message": f"{str(e)}" } )
    except urllib.error.HTTPError as e:
        print( { "code": 601, "message": f"{str(e)}" } )
    except Exception as e:
        print( { "code": 999, "message": f"{str(e)}" } )
