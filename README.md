# schoolclosures
Jadu scripts for displaying schools closed by severe weather. stuart.downie@highland.gov.uk

School_closures.php displays records from a directory which include a phrase (the word 'closed'). 
Update the $directoryID number and relevant field names.

reset_school_closure.php is a scheduled task that runs at 6pm each night. Depending on your setup, you may need to ask
Jadu to configure this for you. It should be installed in the /VAR/ folder so it's not accessible via a browser.
The script checks if there are closures, sends an email of the day's closures (as an XML attachment), 
then resets the values of the closure fields in ALL records of the schools directory.
