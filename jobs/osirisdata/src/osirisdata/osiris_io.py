from pymongo import MongoClient

class OsirisIO:

    def __init__(self, db_info):
        client = MongoClient(db_info['Connection'])
        self.osiris = client[db_info['Database']]

    def getUserId(self, name_last: str, name_first: str = '', orcid=None):
        if orcid:
            user = self.osiris['persons'].find_one({'orcid': orcid})
            if user:
                return user['username']
        user = self.osiris['persons'].find_one(
            {'$or': [
                {'last': name_last, 'first': {'$regex': f'^{name_first}.*'}},
                {'names': f'{name_last}, {name_first}'}
            ]}
        )
        if user:
            return user['username']
        return None
    
    def getJournal(self, issn) -> None:
        return self.osiris['journals'].find_one({'issn': {'$in': issn}})
    

    def addJournal(self, new_journal) -> int: 
        new_doc = self.osiris['journals'].insert_one(new_journal)
        return new_doc.inserted_id
    

    def getActvities(self, startyear=0):
        return self.osiris['activities'].find(
                {
                    'type': 'publication',
                    'year': {'$gte': int(startyear)},
                }, 
                {
                    'title': 1
                })
    
    def checkExistence(self, doi, pubmed):
        if doi and self.osiris["activities"].count_documents({'doi': doi}) > 0:
            print(f'DOI {doi} exists in activities and was omitted.')
            return True
        if pubmed and self.osiris["activities"].count_documents({'pubmed': pubmed}) > 0:
            print(f'Pubmed {pubmed} exists in activities and was omitted.')
            return True
        if self.osiris['queue'].count_documents({'doi': doi}) > 0:
            print(f'DOI {doi} exists in queue and was omitted.')
            return True
        
    def deleteActivity(self, doi):
        # delete all entries with the same DOI
        self.osiris['activities'].delete_many({'doi': doi})

    def addActivity(self, element):
        self.osiris['activities'].insert_one(element)

    def addQueue(self, element):
        self.osiris['queue'].insert_one(element)