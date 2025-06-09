import configparser
import os

from pymongo import MongoClient

from osirisdata.osiris_io import OsirisIO

class Parser:
    config = configparser.ConfigParser()
    path = os.getcwd()      # os.path.dirname(__file__)
    config.read(os.path.join(path, 'config.ini'))

    osiris = OsirisIO(config['Database'])

    mail = config['DEFAULT'].get('AdminMail')