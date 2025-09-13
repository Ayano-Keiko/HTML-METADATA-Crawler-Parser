import json
import time
import urllib
import urllib.request
import urllib.parse
import re
from collections import deque
import os
import sys

headers = {
    'user-agent':
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/139.0.0.0 Safari/537.36'
}

media_types = [
    '.jpg', '.jpeg', '.png', '.gif', ".mp3", ".mpeg", '.webp', '.mp4', '.mov', '.avi', '.wav'
]
stopwords = open('./NLTKstopwords.txt', 'r').read().splitlines()
CLEANR = re.compile('<.*?>')

def fetch_page(url):
    try:
        req = urllib.request.Request(url, headers=headers)
        with urllib.request.urlopen(req, timeout=5) as resp:
            if resp.status != 200:
                return None
            return resp.read().decode("utf-8", errors="ignore")
    except:
        return None

def sql_escape(text):
    '''
    escape dangerous tag away
    :param text:
    :return:
    '''
    if not text:
        return ""

    text = text.replace("\\n", " ")
    return text.replace("'", "\"")

def tokenize_str(text, word_freq):
    '''
    Not use - split word in PHP
    split string by words
    get word frequency
    :param text:
    :return:
    '''
    text = text.lower()
    words = re.findall(r"[a-z0-9]+", text)  # only alphanumeric words

    for w in words:
        w = sql_escape(w)
        if w not in stopwords and not w.isdigit():
            word_freq[w] = word_freq.get(w, 0) + 1


def pagerank(graph, damping=0.85, iterations=20):
    N = len(graph)
    pr = {page: 1.0 / N for page in graph}
    for _ in range(iterations):
        new_pr = {}
        for page in graph:
            rank_sum = (1 - damping) / N
            for other, outlinks in graph.items():
                if page in outlinks and len(outlinks) > 0:
                    rank_sum += damping * (pr[other] / len(outlinks))
            new_pr[page] = rank_sum
        pr = new_pr
    return pr



def spider(seed_url, max_pages):
    visited = set()
    queue = deque([seed_url])
    count = 0  # the number of crwaled websites
    word_freq = {}
    url_details = []
    graph = {}  # the graph of link and score mapping

    while queue and count < max_pages:

        url = queue.popleft()  # current crawl website & remove from begining

        # if url already exists, skip
        if url in visited:
            continue
        visited.add(url)


        try:
            # get the html document
            req = urllib.request.Request(url, headers=headers)
            resp = urllib.request.urlopen(req, timeout=5)

            if resp.status != 200:
                html = ""
            else:
                html = resp.read().decode("utf-8", errors="ignore")

            # retrive title
            title = re.search(r'<title>(.*?)</title>', html, re.IGNORECASE)

            if title:
                title = title.group(1)
            else:
                title = ""

            # retireve metadata description
            description = re.search(r'<meta name="description" content="(.*?)".*>', html, re.IGNORECASE)

            if description:
                description = description.group(1)
            else:
                description = ""

            # retireve metadata keywords
            keywords = re.search(r'<meta name="keywords" content="(.*?)".*>', html, re.IGNORECASE)

            if keywords:
                keywords = keywords.group(1)
            else:
                keywords = ""

            # retrieve content in the body
            # we retrieve the text in the html body but do not use them because MySQL limit the number of query
            body_content = re.search(r'<body.*?>(.*?)</body>', html, re.IGNORECASE | re.DOTALL  | re.MULTILINE)

            if body_content:
                body_content = body_content.group(1)
            else:
                body_content = ""

            # (REMOVE <SCRIPT> to </script> and variations)
            body_content = re.sub(r'<[ ]*script.*?\/[ ]*script[ ]*>', '', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))

            # (REMOVE HTML <STYLE> to </style> and variations)
            body_content = re.sub(r'<[ ]*style.*?\/[ ]*style[ ]*>', '', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))

            # (REMOVE HTML <META> to </meta> and variations)
            body_content = re.sub(r'<[ ]*meta.*?>', '', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))

            # (REMOVE HTML COMMENTS <!-- to --> and variations)
            body_content = re.sub(r'<[ ]*!--.*?--[ ]*>', '', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))

            # (REMOVE HTML DOCTYPE <!DOCTYPE html to > and variations)
            body_content = re.sub(r'<[ ]*\![ ]*DOCTYPE.*?>', '', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))

            body_content = re.sub(r"\s+", " ", body_content, flags=re.UNICODE)

            # remove URL
            body_content = re.sub(r'http\S+', ' ', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))
            # remove numbers (integers and float points
            body_content = re.sub(r'\d+[\.\d+]{0}', ' ', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))
            # remove roma number ( too slow, donot use )
            # body_content = re.sub(r"\b(?=[MDCLXVIΙ])M{0,4}(CM|CD|D?C{0,3})(XC|XL|L?X{0,3})([IΙ]X|[IΙ]V|V?[IΙ]{0,3})\b\.?", '', body_content, flags=(re.IGNORECASE | re.MULTILINE | re.DOTALL))

            url_details.append(
                json.dumps({'title': sql_escape(title), 'description': sql_escape(description), 'url': url, 'keywords': sql_escape(keywords), 'body': sql_escape(body_content)})
            )

            # retrieve 所有带 <a> href里的 pages & add to queue(FIFO) | 需要过滤
            subURLs = re.findall(r'<a\s[^>]*\bhref="([^#"][^"]*)"', html, re.IGNORECASE)
            subLinks = []  # save sub urls

            for subURL in subURLs:
                # retrieve 所有相对url pages
                if not urllib.parse.urlparse(subURL).netloc:

                    if subURL.startswith('..'):
                        # 上级目录里的pages，skip
                        pass
                    elif subURL.endswith('/'):
                        # */ z 类型page
                        subLinks.append(urllib.parse.urljoin(url, subURL))
                    elif os.path.splitext(subURL)[1] in media_types:
                        # media resources --> skip
                        pass
                    elif subURL == '.':
                        pass
                    elif re.match(r'[^@]+@[^@]+\.[^@]+', subURL):
                        # skip the email address
                        pass
                    else:
                        subLinks.append(urllib.parse.urljoin(url, subURL))

                elif subURL not in visited and subURL.startswith(seed_url): # subURL.startswith(seed_url)
                    # url with full path (http/https) pick one start with seed url
                    # only select page under seed url
                    subLinks.append(subURL)
                else:
                    # other page with full path (http/https)
                    pass

            # PageRank
            # 保存page to graph
            graph[url] = subLinks
            queue.extendleft(subLinks)

        except Exception as e:
            continue
        finally:

            count += 1

    return {'URL Table': url_details, 'pr_scores': pagerank(graph), 'number': count}



if __name__ == '__main__':
    url = sys.argv[1] # https://undcemcs01.und.edu/~wen.chen.hu/course/525/ | https://date-a-live5th-anime.com/
    max_number = int(sys.argv[2])  # int(sys.argv[2])  10

    # Time Counting - Just debug & comment them out while real develop
    # start_time = time.time()
    detail = spider( url, max_number )
    # finish_time = time.time()

    # print(f'Program time: {finish_time - start_time} seconds')

    print(json.dumps(detail))
    # print(detail)

