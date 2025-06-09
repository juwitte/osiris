import html
from diophila.openalex import OpenAlex
from Levenshtein import ratio
from nameparser import HumanName
from datetime import datetime
from pprint import pprint

from osirisdata.parser import Parser
from osirisdata.utils.openalex_utils import makeAbstractString, TYPES


def getHistory(element={}):
        return {
            'type': 'imported',
            'user': None,
            'date': datetime.now().date().isoformat(),
            # 'data': element
        }


class OpenAlexParser(Parser):
    def __init__(self, ignore_duplicates=False) -> None:

        self.inst_id = self.config['OpenAlex']['Institution'].upper()
        self.startyear = self.config['DEFAULT']['StartYear']

        # set up OpenAlex
        self.openalex = OpenAlex(self.mail)
        
        self.possible_dupl = []
        if not ignore_duplicates:
            possible_dupl = self.osiris.getActvities(self.startyear)
            self.possible_dupl = [
                (i['_id'], i['title']) for i in possible_dupl
            ]
    

    def getJournal(self, issn):
        if jrnl := self.osiris.getJournal(issn):
            return jrnl

        # if journal does not exist: create one
        source = self.openalex.get_single_venue(issn[-1], "issn")
        if not source or source['type'] != 'journal':
            return None

        new_journal = {
            'journal': source['display_name'],
            'abbr': source['abbreviated_title'],
            'publisher': source['host_organization_name'],
            'issn': source['issn'],
            'oa': source['is_oa'],
            'openalex': source['id'].replace('https://openalex.org/', '')
        }

        new_journal['_id'] = self.osiris.addJournal(new_journal)
        return new_journal


    def parseWork(self, work):
        if work['is_retracted']:
            print('retracted')
            print(work)
            return False

        # print(work['doi'])
        if not work['doi'] or 'https://doi.org/' not in work['doi']:
            print('doi not found')
            print(work)
            return False

        pubmed = work['ids'].get('pmid')
        if pubmed:
            pubmed = pubmed.replace('https://pubmed.ncbi.nlm.nih.gov/', '')

        doi = work['doi'].replace('https://doi.org/', '')
        # print(doi)
        # check if element is in the database
        if self.osiris.checkExistence(doi, pubmed):
            return False

        typ = TYPES.get(work['type'])
        if not typ:
            print(f'Activity type {work["type"]} is unknown (DOI: {doi}).')
            return False

        # print(doi)
        authors = []
        for author in work['authorships']:
            # match via name and ORCID
            name = HumanName(author['author']['display_name'])
            orcid = author['author'].get('orcid')
            if (orcid):
                orcid = orcid.replace('https://orcid.org/', '')

            name_first = name.first
            name_last = name.last
            user = self.osiris.getUserId(name_last, name_first, orcid)
            pos = author['author_position']
            if pos == 'middle' and author.get('is_corresponding'):
                pos = 'corresponding'

            inst = [i.get('id') for i in author['institutions']]
            authors.append({
                'last': name.last,
                'first': name.first + (' ' + name.middle if name.middle else ''),
                'position': pos,
                'aoi': ('https://openalex.org/'+self.inst_id in inst),
                'orcid': orcid,
                'user': user,
                'approved': False
            })

        pages = None
        if work['biblio']['first_page']:
            pages = work['biblio']['first_page']
            if work['biblio']['last_page'] and work['biblio']['last_page'] != pages:
                pages += '-' + work['biblio']['last_page']

        # journal
        loc = work['primary_location']['source']
        # journal = loc['display_name']

        # date
        date = work['publication_date'].split('-')
        month = None
        day = None
        if len(date) >= 2:
            month = int(date[1])
        if len(date) >= 3:
            day = int(date[2])

        abstract = makeAbstractString(work.get('abstract_inverted_index'))
        work['title'] = html.unescape(work['title'])
        element = {
            'doi': doi,
            'type': 'publication',
            'subtype': typ,
            'title': work['title'],
            'year': work['publication_year'],
            'abstract': abstract,
            'month': month,
            'day': day,
            'authors': authors,
            'pages': pages,
            'openalex': work['id'].replace('https://openalex.org/', ''),
            'pubmed': pubmed,
            'open_access': work['open_access']['is_oa'],
            'oa_status': work['open_access']['oa_status'],
            'correction': False,
            'epub': False
        }
        if (typ == 'others'):
            element['doc_type'] = work['type'].title()
        
        journal = None
        if loc and loc.get('type') == 'journal':
            element['location'] = loc['display_name']
            journal = self.getJournal(loc['issn'])
            if journal:
                element.update({
                        'volume': work['biblio']['volume'],
                        'issue': work['biblio']['issue'],
                        'journal': journal['journal'],
                        'issn': journal['issn'],
                        'journal_id': str(journal['_id'])
                    })
                if (not element['volume']) and not element['issue']:
                    element['epub'] = True

        if (typ == 'article'):
            if not loc or not loc['issn']:
                element['subtype'] = 'magazine'
            elif loc.get('type')== 'repository':
                element['subtype'] = 'preprint'
            elif not journal:
                element['subtype'] = 'magazine'

        if (typ == 'chapter' and loc and loc.get('display_name')):
            element.update({
                'book': loc['display_name'],
                'issn': loc['issn'],
                
            })
        if typ == 'preprint':
            element['subtype'] = 'preprint'
        
        if (typ == 'magazine' or typ == 'preprint'):
            element['magazine'] = loc.get('display_name') if loc else None

        for id, dupl in self.possible_dupl:
            dist = ratio(dupl, element['title'])
            # print(dist, dupl)
            if (dist > 0.9):
                element['duplicate'] = id
                break
        return element
    
    def get_work(self, id, idtype='doi', ignoreDupl=True, test=False):
        if (test):
            # delete all entries with the same DOI
            self.osiris.deleteActivity(id)
        work = self.openalex.get_single_work(id, idtype)
        element = self.parseWork(work)
        if test:
            pprint(element)
        if (element != False):
            if ignoreDupl and element.get('duplicate'):
                print(f'Activity might have a duplicate (DOI {element["doi"]}) and was omitted.')
                return
            self.osiris.addActivity(element)
            print(f'{idtype.upper()} {id} has been added to the database.')
    

    def get_works_dois(self, filters=None):
        if not filters:
            filters = {
                "from_publication_date": self.startyear + "-01-01",
                "institutions.id": self.inst_id,
                "has_doi": 'true'
            }
        pages_of_works = self.openalex.get_list_of_works(filters=filters, pages=None)
        for page in pages_of_works:
            for work in page['results']:
                yield work['doi']
                    
    def get_works(self, filters=None):
        # NOPE: use created_date and updated_date to filter
        # Not possible, needs payed version

        if not filters:
            filters = {
                "from_publication_date": self.startyear + "-01-01",
                "institutions.id": self.inst_id,
                "has_doi": 'true'
            }

        pages_of_works = self.openalex.get_list_of_works(filters=filters, pages=None)

        works_count = 0
        for page in pages_of_works:
            for work in page['results']:
                try: 
                    element = self.parseWork(work)
                    if element == False: continue
                    works_count+=1
                    yield element
                except Exception as e:
                    print(f'Error with DOI {work["doi"]}')
                    print(e)
                    continue
        print(f'--- Finished. Imported {works_count} documents.')
    
    
    def queueJob(self):
        for element in self.get_works():
            print(element)
            self.osiris.addQueue(element)
    

    def importJob(self):
        for element in self.get_works():
            if element.get('duplicate'):
                print(f'Activity might have a duplicate (DOI {element["doi"]}) and was omitted.')
                continue
            element['imported'] = datetime.now().date().isoformat()
            element['history'] = [getHistory(element)]
            self.osiris.addActivity(element)


if __name__ == '__main__':
    parser = OpenAlexParser()
    # parser.queueJob()
    
    parser.get_work('10.1007/978-3-319-69075-9_13', test=True)
