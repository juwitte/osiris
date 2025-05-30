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