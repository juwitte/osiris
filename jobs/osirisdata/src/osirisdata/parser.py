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
            user = Parser.osiris['persons'].find_one({'orcid': orcid})
            if user:
                return user['username']
        user = Parser.osiris['persons'].find_one(
            {'$or': [
                {'last': name_last, 'first': {'$regex': f'^{name_first}.*'}},
                {'names': f'{name_last}, {name_first}'}
            ]}
        )
        if user:
            return user['username']
        return None