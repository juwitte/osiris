
def make_abstract_string(inverted_abstract : dict[str, list[int]]):
        if not inverted_abstract: return None
        
        # search largest position and adjust array size
        max_value = max([max(x) for x in inverted_abstract.values()])
        abstract = [''] * (1 + max_value)
        
        for word, positions in inverted_abstract.items():
            for pos in positions:
                abstract[pos] = word
        return " ".join(abstract)

TYPES = {
    "book-section": "chapter",
    "monograph": "book",
    "report-component": "others",
    "report": "others",
    "peer-review": "others",
    "book-track": "book",
    "journal-article": "article",
    "article": "article",
    "book-part": "book",
    "other": "others",
    "book": "book",
    "journal-volume": "article",
    "book-set": "book",
    "reference-entry": "others",
    "proceedings-article": "others",
    "journal": "others",
    "component": "others",
    "book-chapter": "chapter",
    "proceedings-series": "others",
    "report-series": "others",
    "proceedings": "others",
    "database": "others",
    "standard": "others",
    "reference-book": "book",
    "posted-content": "others",
    "journal-issue": "others",
    "dissertation": "dissertation",
    "grant": "others",
    "dataset": "others",
    "book-series": "book",
    "edited-book": "book",
    "review": "magazine",
    "preprint": "preprint",
}