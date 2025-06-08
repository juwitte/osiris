
# Setup optional jobs

All optional jobs need Python to be installed. Please look up [how to install](https://wiki.python.org/moin/BeginnersGuide/Download) the latest Python version on your OS. You will also need `pip` to install packages.

When you have Python installed, you can create an virtual environment (see [here](https://docs.python.org/3/library/venv.html) for more information on python virtual environments). 

This command will create a folder named 'venv' and all needed files for the virtual environment with in the folder:

```bash
python3 -m venv ./venv
```

Next step is to start the virtual environment:

```bash
source ./venv/bin/activate
```

## Set up osirisdata

Now you can install the python library **osirisdata** and all dependencies. Therefore you can switch directory into the `jobs` folder and run:

```bash
make install
```

Additionally, you have to copy the **configuration file** `config.default.ini` in the jobs folder and rename it to `config.ini`. 
In this file, you must modify the values according to your needs. 


## Setup the queue job feature

The queue job gets new activities from online sources and saves them in a queue. Users will be informed when new activities are waiting in the queue and they can easily add them to OSIRIS.


### Prepare

To set up this feature, you must change the OpenAlex-ID of your institute in the `config.ini` file.

```ini
[OpenAlex]
Institution = I7935750
```

### Init Cron Job

Finally, we init a cron job on the device. We use the editor nano for this (default on most devices is vi). The following settings are used to run the job weekly (2 a.m. on Sunday).

```bash
EDITOR=nano crontab -e 

# enter this as cronjob:
0 2 * * 0 python3 /var/www/html/jobs/openalex_parser.py

# press Ctrl+O to save and Ctrl+X to exit
```
