install:
	pip3 install osirisdata/

uninstall:
	pip3 uninstall osirisdata -y

reinstall: uninstall install

runTests:
	@python3 ./osirisdata/tests/test_OpenAlexParser.py > ./osirisdata/tests/test_output.txt
	@echo DONE