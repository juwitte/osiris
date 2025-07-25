'''
Import publications from CrossRef API by DOI
Parse publication metadata and import it into the database
'''


import requests
import re

from osirisdata.parser import Parser
from osirisdata.utils.crossref_utils import TYPES

class CrossRefParser(Parser):
    def __init__(self): 

        self.affiliation = self.config['DEFAULT'].get('AffiliationRegex')
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
    
    def queue_all_unknown_dois(self):
        # Work in progress
        # get 'https://api.crossref.org/works?query.affiliation={institute name or ror}'
        # searches text in affiliation strings
        pass
        

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
    
    def get_type(self, pub_type):
        selected_type = pub_type
        pub_type = pub_type.lower()
        if pub_type in TYPES:
            selected_type = TYPES[pub_type]
        cat = self.osiris.get_type({'id': selected_type})
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
            for idx, author in enumerate(pub['author']):
                aoi = any(self.affiliation.search(aff.get('name', '')) for aff in author.get('affiliation', []))
                pos = author.get('sequence', 'middle')
                if idx == 0:
                    pos = 'first'
                elif idx == len(pub['author']) - 1:
                    pos = 'last'
                name = {
                    'last': author.get('family') or author.get('name'),
                    'first': author.get('given'),
                    'affiliation': aoi,
                    'position': pos,
                    'user': None
                }
                if 'orcid' in author:
                    name['orcid'] = author['orcid']
                name['user'] = self.osiris.get_user_id(name.get('last'), name.get('first'), name.get('orcid'))
                if author.get('sequence') == 'first':
                    first = idx + 1
                authors.append(name)

        issue = pub.get('journal-issue', {}).get('issue', None)
        funder = [award for f in pub.get('funder', []) if 'award' in f for award in f['award']]
        pages = pub.get('page') or pub.get('article-number', None)

        abstract = pub.get('abstract', '')
        if abstract:
            abstract = re.sub(r'<[^>]*>?', '', abstract)  # Remove HTML tags
            abstract = re.sub(r'\s\s+', ' ', abstract)  # Remove line breaks and extra spaces
            abstract = re.sub(r'^abstract', '', abstract, flags=re.I).strip()  # Remove leading "abstract"

        element = {
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
        
        types = self.get_type(pub.get('type'))
        if not types:
            return None
        element['type'] = types['type']
        element['subtype'] = types['subtype']

        if element['type'] == 'article':
            pub['book'] = pub.pop('journal', None)
        elif element['type'] == 'book':
            if 'editors' in pub and len(pub['editors']) > 0 and 'authors' in pub and len(pub['authors']) > 0:
                element['type'] = 'chapter'
            elif 'editors' in pub and len(pub['editors']) > 0:
                pub['book'] = pub.pop('journal', None)
            else:
                pub['series'] = pub.pop('journal', None)
        
        return element
        

if __name__ == '__main__':
    parser = CrossRefParser()
    dois = ['10.1007/978-3-642-18156-6_6', '10.1158/0008-5472.can-06-2615', ]

    for data in parser.get_works(dois):
        element = parser.parse_metadata(data)
        print(element)

        # old:
        # parser.osiris['publications'].insert_one(element)
        # print(f'Publication with DOI {element["doi"]} was imported.')
        # element['imported'] = datetime.now().date().isoformat()
        # element['history'] = [data]
        # parser.osiris['publications'].insert_one(element)
        # print(f'Publication with DOI {element["doi"]} was imported.')
