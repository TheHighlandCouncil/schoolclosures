# schoolclosures
Jadu scripts for displaying schools closed by severe weather. stuart.downie@highland.gov.uk

School_closures.php displays records from a directory which include a phrase (the word 'closed'). 
Update the $directoryID number and relevant field names.

reset_school_closure.php is a scheduled task that runs at 6pm each night. Depending on your setup, you may need to ask
Jadu to configure this for you. It should be installed in the /VAR/ folder so it's not accessible via a browser.
The script checks if there are closures, sends an email of the day's closures (as an XML attachment), 
then resets the values of the closure fields in ALL records of the schools directory.

To enable email and social media alerts for closures:

At The Highland Council, our customer call centre is notified of the closure by the Head Teacher, closes the school in the Jadu directory, then uses a form in our CRM system that sends out emails to local councillors / catering and cleaning staff etc. To include Twitter into the mix, we use tweetymail.com (receives an email, posts it to a Twitter account) this service costs $40 a year. If you use this, remember to configure tweetymail to strip out any corporate email sinature, or the message will be too long to tweet. You may want to have a Twitter > Facebook connection set up too.
