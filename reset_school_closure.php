<?php
	include_once("utilities/JaduStatus.php");
	include_once("directoryBuilder/JaduDirectorySettings.php");	
	include_once("directoryBuilder/JaduDirectories.php");
	include_once("directoryBuilder/JaduDirectoryFields.php");
	include_once("directoryBuilder/JaduDirectoryEntries.php");	
	include_once("directoryBuilder/JaduDirectoryEntryValues.php");
	
	$directoryID = 30; // Set this to the directory ID
	$query = 'Closed'; // We'll look for this phrase inside ALL fields in ALL records
	$pupilCount = 0; // Starting number for pupil count. Pupil count is used to identify if schools are closed
	
	// Create variables from fields in the directory	
	$directoryFields = getAllDirectoryFields($directoryID);
	foreach ($directoryFields as $field) {
		if ($field->title == 'Pupil roll') {
			$pupilCountIDs[] = $field->id; // this one's an array so pupil roll can be summed later
			$pupilRoll = $field->id; // single variable, used to add pupil roll value of each school into XML
		}
		if ($field->title == 'Temporary closure') {
			$statusFieldID = $field->id;			
		}
		if ($field->title == 'Reason for closure') {
			$reasonFieldID = $field->id;			
		}
		if ($field->title == 'School type') { 
			$schoolType = $field->id;		
		}
	}	
	
	// Query all records and build list of closures
	$records = searchDirectoryEntryValues($directoryID, -1, $query, true);	// built-in Jadu function. Returns records that contain a phrase.
	foreach ($records as $record) {
		$recordValues = getAllDirectoryEntryValues($record->id);
		$directoryEntry = getDirectoryEntry($record->id);
		$timeClosed = date("g:i a", $directoryEntry->modDate); // format modDate, which comes as a UNIX timestamp 
		$day = date("d/m/Y");
		$schoolname = trim($directoryEntry->title); // trim used to tidy white spaces in schools info

		if (isset($directoryEntry) && $directoryEntry->id != -1) {
			// Each result is formatted as XML. Instead of printing each result, they're stored in the $closed array which can then be inserted into an XML file.
			$closed[] = '<School><Name>' . $schoolname . '</Name><Type>' . $recordValues[$schoolType]->value . '</Type><Date>' . $day . '</Date><Time>' . $timeClosed . '</Time><Closure>' . $recordValues[$statusFieldID]->value .  '</Closure><Reason>' . $recordValues[$reasonFieldID]->value . '</Reason><Roll>' . $recordValues[$pupilRoll]->value . '</Roll></School>';
		}
		
		// values from fields in $pupilCountIDs array put into $entryValues array
		foreach ($pupilCountIDs as $fieldID) {
			$entryValues[] = $recordValues[$fieldID];
		}		
	}
	// loop through $entryValues array and set $pupilCount to pupil roll total
	foreach ($entryValues as $entryValue) {
		if (!empty($entryValue->value) && $entryValue->value > 0) {
			$pupilCount = $pupilCount + $entryValue->value;
		}
	}	
			
	/* Create an XML Document with closures */		
		$xmlDoc = new DOMDocument();
		$xmlDoc->loadXML("<root/>");
		$f = $xmlDoc->createDocumentFragment();
		// Insert list of closures into the document (implode creates a string with all the values from an array)
		 $f->appendXML(implode(' ', $closed) );
		$xmlDoc->documentElement->appendChild($f);				
		
	/* Build the email  */		
		$mail_to   = "email@yourcouncil.gov.uk"; 
		$from_mail = "webmaster@yourcouncil.gov.uk";
		$from_name = "School closures";
		$reply_to  = "webmaster@yourcouncil.gov.uk";
		$date = date("l j F");	
		$subject   = "Schools closed $date";
		
		/* Plain-text part of the message */		
			$message   = "Please insert the attached XML file into a spreadsheet to analyse closed schools.";
		
		/* Attachment File */		
			// No need to create an actual file, just use the content of the XML document...
			$content = chunk_split(base64_encode($xmlDoc->saveXML()));
			
		/* Create the email header */			
			// Generate a boundary
			$boundary = md5(uniqid(time()));
			
			// Email header
			$header = "From: ".$from_name." <".$from_mail.">\r\n";
			$header .= "Reply-To: ".$reply_to."\r\n";
			$header .= "MIME-Version: 1.0\r\n";
			
			// Multipart wraps the Email Content and Attachment
			$header .= "Content-Type: multipart/mixed; boundary=\"".$boundary."\"\r\n";
			$header .= "This is a multi-part message in MIME format.\r\n";
			$header .= "--".$boundary."\r\n";
			
			// text/plain
			$header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
			$header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
			$header .= "$message\r\n";
			$header .= "--".$boundary."\r\n";
			
			// Attachment
			$header .= "Content-Type: application/xml; name=\""."closures.xml"."\"\r\n";
			$header .= "Content-Transfer-Encoding: base64\r\n";
			$header .= "Content-Disposition: attachment; filename=\""."closures.xml"."\"\r\n\r\n";
			$header .= $content."\r\n";
			$header .= "--".$boundary."--";			

	
/* If there are any closures, send the email then reset the field values in the directory */
		
	if ($pupilCount > 0) {

	// send the email
		mail($mail_to, $subject, $message, $header); 

	// Code to reset field values below...
	
		$fieldValueToUpdate = NULL; // field values will be replaced with this
		
		// Reset closure field
		$entries = getAllDirectoryEntries($directoryID);	
		foreach ($entries as $entry) {
			$entryValues = getAllDirectoryEntryValuesForField($statusFieldID);	
			foreach($entryValues as $entryValue) {
				$entryValue->value = $fieldValueToUpdate;
				updateDirectoryEntryValue($entryValue);
			}
		}

		sleep(5); // halt the program for 5 seconds, then run the next step...
		
		// Reset closure reason field
		$entries = getAllDirectoryEntries($directoryID);
		foreach ($entries as $entry) {
			$entryValues = getAllDirectoryEntryValuesForField($reasonFieldID);
			foreach($entryValues as $entryValue) {
				$entryValue->value = $fieldValueToUpdate;
				updateDirectoryEntryValue($entryValue);
			}
		updateDirectoryEntry($entry); // save each directory record
		}				
	}
?>
