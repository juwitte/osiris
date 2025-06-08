import configparser
import os

from pymongo import MongoClient

class Parser:
    config = configparser.ConfigParser()
    path = os.getcwd()      # os.path.dirname(__file__)
    config.read(os.path.join(path, 'config.ini'))

    # set up database connection
    client = MongoClient(config['Database']['Connection'])
    osiris = client[config['Database']['Database']]

    mail = config['DEFAULT'].get('AdminMail')

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
    
    def getJournal(self, issn):
        return self.osiris['journals'].find_one({'issn': {'$in': issn}})
    
    def addJournal(self, new_journal):
        new_doc = self.osiris['journals'].insert_one(new_journal)
        return new_doc.inserted_id