install:
	pip3 install osirisdata/

uninstall:
	pip3 uninstall osirisdata -y

reinstall: uninstall install

runTests:
	@python3 ./osirisdata/tests/test_OpenAlexParser.py > /dev/null
	@python3 ./osirisdata/src/osirisdata/crossref_parser.py > /dev/null
	@echo DONE