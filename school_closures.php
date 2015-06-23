<?php
	include_once("utilities/JaduStatus.php");
	include_once("directoryBuilder/JaduDirectorySettings.php");	
	include_once("directoryBuilder/JaduDirectories.php");
	include_once("directoryBuilder/JaduDirectoryFields.php");
	include_once("directoryBuilder/JaduDirectoryEntries.php");	
	include_once("directoryBuilder/JaduDirectoryEntryValues.php");
?>

<h2>Schools closed <?php echo $date = date("l j F");?></h2>

<p>This page is updated from 7 am and cleared each evening. Get alerts of closures on <a href="https://twitter.com/yourcouncil">Twitter</a> and <a href="https://www.facebook.com/yourcouncil">Facebook.</a></p>

<?php	
	$directoryName = getAllDirectories();
	foreach ($directoryName as $dirname) {
		if ($dirname->name == 'Schools')  // Name of schools directory
		{
		 $directoryID = $dirname->id;
		}	
	}
	
	$query = 'Closed'; // We'll look for this phrase inside ALL fields in ALL records
	
	$pupilCount = 0; // Initial value for counting total pupil roll
	$secondaryPupilCount = 0; // Initial value for counting pupil roll
	$primaryPupilCount = 0; // Initial value for counting pupil roll
	$specialPupilCount = 0; // Initial value for counting pupil roll
	$resPupilCount = 0; // Initial value for counting pupil roll		
	$nurseryPupilCount = 0; // Initial value for counting pupil roll	
	
	// Create variables from fields in directory
	$directoryFields = getAllDirectoryFields($directoryID);
	foreach ($directoryFields as $field) {
		if ($field->title == 'Pupil roll') {
			$pupilCountIDs[] = $field->id; // This one's an array so its values can be summed later
		}
		if ($field->title == 'Temporary closure') {
			$statusFieldID = $field->id;			
		}
		if ($field->title == 'Reason for closure') {
			$reasonFieldID = $field->id;			
		}
		if ($field->title == 'School type') { 
			$schoolType[] = $field->id; // This one's an array so it can be counted later		
		}
	}	
	
	// Query all records and build list of closures
	$records = searchDirectoryEntryValues($directoryID, -1, $query, true);	// built-in Jadu function. Returns records that contain a phrase.
	foreach ($records as $record) {
		$recordValues = getAllDirectoryEntryValues($record->id);
		$directoryEntry = getDirectoryEntry($record->id);
		$timeClosed = date("g:i a", $directoryEntry->modDate); // format modDate which comes as a UNIX timestamp 
		$schoolname = trim($directoryEntry->title); // trim needed to remove white spaces in schools info

		if (isset($directoryEntry) && $directoryEntry->id != -1) {
			echo '<span><strong>' . $schoolname . '&nbsp;' . $timeClosed . '&nbsp;</strong>: &nbsp;' . $recordValues[$statusFieldID]->value .  '&nbsp;(' . $recordValues[$reasonFieldID]->value . ')</span><br/>';
		}
		
		// values from fields in $pupilCountIDs array put into $entryValues array
		foreach ($pupilCountIDs as $fieldID) {
			$entryValues[] = $recordValues[$fieldID];
		}
		
		// Field values from $schoolType array used to build arrays of school names and arrays of pupil rolls
		foreach ($schoolType as $schoolTypeValue) {
			if ($recordValues[$schoolTypeValue]->value == 'Secondary') {
				// create an array of school names for counting
				$secondaryentries[] = $recordValues[$schoolTypeValue];
					// Create array with secondary pupil roll
					foreach ($pupilCountIDs as $fieldIDsecondary) {
						$secondaryroll[] = $recordValues[$fieldIDsecondary];
					}			
			}
			if ($recordValues[$schoolTypeValue]->value == 'Primary') {
				// create an array of school names for counting			
				$primaryentries[] = $recordValues[$schoolTypeValue];
					// Create array with primary pupil roll				
					foreach ($pupilCountIDs as $fieldIDprimary) {
						$primaryroll[] = $recordValues[$fieldIDprimary];
					}			
			}
			if ($recordValues[$schoolTypeValue]->value == 'Special') {
				// create an array of school names for counting			
				$specialentries[] = $recordValues[$schoolTypeValue];
					// Create array with primary pupil roll				
					foreach ($pupilCountIDs as $fieldIDspecial) {
						$specialroll[] = $recordValues[$fieldIDspecial];
					}			
			}
			if ($recordValues[$schoolTypeValue]->value == 'School Residence') {
				// create an array of school names for counting			
				$resentries[] = $recordValues[$schoolTypeValue];
					// Create array with primary pupil roll				
					foreach ($pupilCountIDs as $fieldIDres) {
						$resroll[] = $recordValues[$fieldIDres];
					}			
			}			
			if ($recordValues[$schoolTypeValue]->value == 'Nursery') {
				// create an array of school names for counting			
				$nurseryentries[] = $recordValues[$schoolTypeValue];
					// Create array with nursery pupil roll				
					foreach ($pupilCountIDs as $fieldIDnursery) {
						$nurseryroll[] = $recordValues[$fieldIDnursery];
					}					
			}			
		}		
	}

	// loop through arrays and determine pupil roll totals
	foreach ($entryValues as $entryValue) {
		if (!empty($entryValue->value) && $entryValue->value > 0) {
			$pupilCount = $pupilCount + $entryValue->value;
		}
	}
	foreach ($secondaryroll as $secondaryrollValue) {
		if (!empty($secondaryrollValue->value) && $secondaryrollValue->value > 0) {
			$secondaryPupilCount = $secondaryPupilCount + $secondaryrollValue->value;
		}
	}
	foreach ($primaryroll as $primaryrollValue) {
		if (!empty($primaryrollValue->value) && $primaryrollValue->value > 0) {
			$primaryPupilCount = $primaryPupilCount + $primaryrollValue->value;
		}
	}
	foreach ($specialroll as $specialrollValue) {
		if (!empty($specialrollValue->value) && $specialrollValue->value > 0) {
			$specialPupilCount = $specialPupilCount + $specialrollValue->value;
		}
	}
	foreach ($resroll as $resrollValue) {
		if (!empty($resrollValue->value) && $resrollValue->value > 0) {
			$resPupilCount = $resPupilCount + $resrollValue->value;
		}
	}		
	foreach ($nurseryroll as $nurseryrollValue) {
		if (!empty($nurseryrollValue->value) && $nurseryrollValue->value > 0) {
			$nurseryPupilCount = $nurseryPupilCount + $nurseryrollValue->value;
		}
	}		
		
	
	// Build final sentences with pupil roll tally...	
	$total = count($entryValues); // total number of schools and nurseries closed
	$secondaryTotal = '<strong>Secondary schools</strong>: ' . count($secondaryentries) . '  (' . $secondaryPupilCount . ' pupils)<br />' ;
	$primaryTotal = '<strong>Primary schools</strong>: ' . count($primaryentries) . '  (' . $primaryPupilCount . ' pupils)<br />' ;
	$specialTotal = '<strong>Special schools</strong>: ' . count($specialentries) . '  (' . $specialPupilCount . ' pupils)<br />' ;	
	$resTotal = '<strong>School residences</strong>: ' . count($resentries) . '  (' . $resPupilCount . ' pupils)<br />' ;		
	$nurseryTotal = '<strong>Nurseries</strong>: ' . count($nurseryentries) . '  (' . $nurseryPupilCount . ' children)<br />';

		if ($secondaryPupilCount == 0) { // Make the $secondaryTotal sentence vanish if 0 are closed
			$secondaryTotal = NULL;
		}	
		if ($primaryPupilCount == 0) { // Make the $primaryTotal sentence vanish if 0 are closed
			$primaryTotal = NULL;
		}
		if ($specialPupilCount == 0) { // Make the $specialTotal sentence vanish if 0 are closed
			$specialTotal = NULL;
		}		
		if ($resPupilCount == 0) { // Make the $resTotal sentence vanish if 0 are closed
			$resTotal = NULL;
		}		
		if ($nurseryPupilCount == 0) { // Make the $nurseryTotal sentence vanish if 0 are closed
			$nurseryTotal = NULL;
		}		
	
	$sentence = '<div><h2>Total schools closed</h2>' . $secondaryTotal . $primaryTotal . $specialTotal . $resTotal . $nurseryTotal . '</div>';
	
		if ($pupilCount == 0) { // remove all totals if 0 schools closed 
			$sentence = '<strong>No schools have been closed.</strong>';
		}
	
	echo $sentence;	
?>
