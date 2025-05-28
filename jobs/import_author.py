author = 'a5102003692'

from jobs.osirisdata.src.osirisdata.openalex_parser import OpenAlexParser
from jobs.osirisdata.src.osirisdata.crossref_parser import CrossrefParser
from datetime import datetime


openalex = OpenAlexParser()
crossref = CrossrefParser()

filters = {
    "from_publication_date": openalex.startyear + "-01-01",
    # "institutions.id": parser.inst_id,
    "has_doi": 'true',
    "author.id": author
}

for element in openalex.get_works(filters):
    # print(element)
    if element.get('duplicate'):
        # print(f'Activity might have a duplicate (DOI {element["doi"]}) and was omitted.')
        continue
    
    doi = element.get('doi')
    if not doi:
        # print(f'Activity has no DOI and was omitted.')
        continue
    
    
    print(f'Activity with DOI {element["doi"]} was imported.')
    element['imported'] = datetime.now().date().isoformat()
    element['history'] = [openalex.getHistory(element)]
    openalex.osiris['activities'].insert_one(element)