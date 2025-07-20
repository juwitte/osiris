
from openalex_parser import OpenAlexParser


def update():
    openalex = OpenAlexParser()
    openalex.update_job()
    #TODO add crossref update
    