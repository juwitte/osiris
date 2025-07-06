from pymongo import MongoClient

class OsirisIO:

    def __init__(self, db_info):
        client = MongoClient(db_info['Connection'])
        self.osiris = client[db_info['Database']]

    def get_user_id(self, name_last: str, name_first: str = '', orcid=None):
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
    
    def get_journal(self, issn) -> None:
        return self.osiris['journals'].find_one({'issn': {'$in': [issn]}})
    

    def add_journal(self, new_journal) -> int: 
        new_doc = self.osiris['journals'].insert_one(new_journal)
        return new_doc.inserted_id
    

    def check_existence(self, doi, pubmed):
        if doi and self.osiris["activities"].count_documents({'doi': doi}) > 0:
            print(f'DOI {doi} exists in activities and was omitted.')
            return True
        if pubmed and self.osiris["activities"].count_documents({'pubmed': pubmed}) > 0:
            print(f'Pubmed {pubmed} exists in activities and was omitted.')
            return True
        if self.osiris['queue'].count_documents({'doi': doi}) > 0:
            print(f'DOI {doi} exists in queue and was omitted.')
            return True

    def get_activities(self, startyear=0):
        return self.osiris['activities'].find(
                {
                    'type': 'publication',
                    'year': {'$gte': int(startyear)},
                }, 
                {
                    'title': 1
                })

    def add_activity(self, element):
        self.osiris['activities'].insert_one(element) 
   
    def delete_activity(self, doi):
        # delete all entries with the same DOI
        self.osiris['activities'].delete_many({'doi': doi})

    def add_queue(self, element):
        self.osiris['queue'].insert_one(element)

    def get_type(self, element):
        # element = {id: searchterm}
        return self.osiris['adminTypes'].find_one(element)
    
    def update_single_entry(self, element):
        pass