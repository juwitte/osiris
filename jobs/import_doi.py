from osirisdata.openalex_parser import OpenAlexParser

parser = OpenAlexParser()
with open('jobs/doi.csv') as f:
    for line in f:
        line = line.strip()
        try:
            parser.add_work(line, ignore_duplicates=True)
        except Exception as e:
            print(f'''
            There was an error with the DOI: {line}
            \t{e}
            The DOI will be skipped.
            ''')
            print(line)
            continue