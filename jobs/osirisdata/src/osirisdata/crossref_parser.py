'''
Import publications from CrossRef API by DOI
Parse publication metadata and import it into the database
'''

from pymongo import MongoClient
import requests
import json
import os
import configparser
import re
from datetime import datetime

class CrossRefParser:
    def __init__(self):
        config = configparser.ConfigParser()
        path = os.getcwd()      # os.path.dirname(__file__)
        config.read(os.path.join(path, 'config.ini'))      
        
        # set up database connection
        client = MongoClient(config['Database']['Connection'])
        self.osiris = client[config['Database']['Database']]

        self.mail = config['DEFAULT'].get('AdminMail')
        self.affiliation = config['DEFAULT'].get('AffiliationRegex')
        self.affiliation = re.compile(self.affiliation)
        
        self.api_url = 'https://api.crossref.org/works/'
        
    def get_works(self, dois):
        for doi in dois:
            yield self.get_work(doi)
    
    def get_work(self, doi):
        response = requests.get(self.api_url + doi)
        if response.status_code == 200:
            data = response.json()
            return data
        else:
            print(f'Error: {response.status_code}')
    
    def get_publishing_date(self, pub):
        date = ["", "", ""]
        if 'published-print' in pub:
            date = self.get_date(pub['published-print'])
        elif 'published' in pub:
            date = self.get_date(pub['published'])
        elif 'published-online' in pub:
            date = self.get_date(pub['published-online'])
        return date

    def get_date(self, publishing_info):
        if 'date-parts' in publishing_info:
            date_parts = publishing_info['date-parts'][0]
            return date_parts + [""] * (3 - len(date_parts))  # Ensure 3 elements in the list
        return ["", "", ""]
    
    
    def getUserId(self, name, orcid=None):
        if orcid:
            user = self.osiris['persons'].find_one({'orcid': orcid})
            if user:
                return user['username']
        user = self.osiris['persons'].find_one(
            {'$or': [
                {'last': name['last'], 'first': {'$regex': '^'+name['first']+'.*'}},
                {'names': f'{name['last']}, {name['first']}'}
            ]}
        )
        if user:
            return user['username']
        return None
    
    def getType(self, pub_type):
        TYPES = {
            "journal-article": "article",
            "magazine-article": "magazine",
            "book-chapter": "chapter",
            "publication": "article",
            "doctoral-thesis": "dissertation",
            "master-thesis": "dissertation",
            "bachelor-thesis": "dissertation",
            "reviewer": "review",
            "editor": "editorial",
            "monograph": "book",
            "edited-book": "book",
            "posted-content": "preprint",
            "peer-review": "review",
            "report": "others",
            "working-paper": "others",
            "conference-paper": "others",
            "proceedings-article": "others",
            "conference-proceedings": "others",
            "conference-poster": "others",
            "conference-abstract": "others",
            "conference-review": "others",
            "conference-report": "others",
        }
        selected_type = pub_type
        pub_type = pub_type.lower()
        if pub_type in TYPES:
            selected_type = pub_type
        
        cat = self.osiris['adminTypes'].find_one({'id': selected_type})
        if not cat:
            return None

        return {
            'type': cat['parent'],
            'subtype': cat['id']
        }
        
        
        

    def parse_metadata(self, data):
        pub = data['message']
        
        date = self.get_publishing_date(pub)
        if 'journal-issue' in pub:
            if ('published-online' in pub['journal-issue'] and 'date-parts' in pub['journal-issue']['published-online']) or \
            ('published-print' in pub['journal-issue'] and 'date-parts' in pub['journal-issue']['published-print']):
                date = self.get_publishing_date(pub['journal-issue'])

        authors = []
        first = 1
        if 'author' not in pub and 'editor' in pub:
            pub['author'] = pub['editor']

        if 'author' in pub:
            for i, a in enumerate(pub['author']):
                aoi = any(self.affiliation.search(aff.get('name', '')) for aff in a.get('affiliation', []))
                pos = a.get('sequence', 'middle')
                if i == 0:
                    pos = 'first'
                elif i == len(pub['author']) - 1:
                    pos = 'last'
                name = {
                    'last': a.get('family') or a.get('name'),
                    'first': a.get('given'),
                    'affiliation': aoi,
                    'position': pos,
                    'user': None
                }
                if 'orcid' in a:
                    name['orcid'] = a['orcid']
                name['user'] = self.getUserId(name, name.get('orcid'))
                if a.get('sequence') == 'first':
                    first = i + 1
                authors.append(name)

        issue = pub.get('journal-issue', {}).get('issue', None)
        funder = [award for f in pub.get('funder', []) if 'award' in f for award in f['award']]
        pages = pub.get('page') or pub.get('article-number', None)

        abstract = pub.get('abstract', '')
        if abstract:
            abstract = re.sub(r'<[^>]*>?', '', abstract)  # Remove HTML tags
            abstract = re.sub(r'\s\s+', ' ', abstract)  # Remove line breaks and extra spaces
            abstract = re.sub(r'^abstract', '', abstract, flags=re.I).strip()  # Remove leading "abstract"

        pubdata = {
            'title': pub.get('title', [None])[0],
            'first_authors': first,
            'authors': authors,
            'year': date[0],
            'month': date[1],
            'day': date[2],
            'type': pub.get('type'),
            'journal': pub.get('container-title', [None])[0],
            'issn': " ".join(pub.get('ISSN', [])),
            'issue': issue,
            'volume': pub.get('volume'),
            'pages': pages,
            'doi': pub.get('DOI'),
            'abstract': abstract,
            'publisher': pub.get('publisher') or pub.get('publisher-name'),
            'isbn': pub.get('ISBN'),
            'city': pub.get('publisher-location'),
            'epub': not ('published-print' in pub or 'published-online' in pub),
            'funding': ','.join(funder)
        }
        
        types = self.getType(pub.get('type'))
        if not types:
            return None
        pubdata['type'] = types['type']
        pubdata['subtype'] = types['subtype']

        if pubdata['type'] == 'article':
            pub['book'] = pub.pop('journal', None)
        elif pubdata['type'] == 'book':
            if 'editors' in pub and len(pub['editors']) > 0 and 'authors' in pub and len(pub['authors']) > 0:
                pubdata['type'] = 'chapter'
            elif 'editors' in pub and len(pub['editors']) > 0:
                pub['book'] = pub.pop('journal', None)
            else:
                pub['series'] = pub.pop('journal', None)
        
        return pubdata
        

if __name__ == '__main__':
    parser = CrossRefParser()
    dois = ['10.1007/978-3-642-18156-6_6', '10.1158/0008-5472.can-06-2615']
    
    for data in parser.get_works(dois):
        pubdata = parser.parse_metadata(data)
        print(pubdata)
        # parser.osiris['publications'].insert_one(pubdata)
        # print(f'Publication with DOI {pubdata["doi"]} was imported.')
        # pubdata['imported'] = datetime.now().date().isoformat()
        # pubdata['history'] = [data]
        # parser.osiris['publications'].insert_one(pubdata)
        # print(f'Publication with DOI {pubdata["doi"]} was imported.')
