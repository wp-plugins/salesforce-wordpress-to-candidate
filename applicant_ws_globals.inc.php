<?php

	/* applicant globals */

	unset($applicantCollection);
	$applicantCollection = array();

	$applicantCollection['Name'] = 	filter_input(INPUT_POST,'your_name',FILTER_SANITIZE_STRING); 	// name
	$applicantCollection['SFDC_Candidate_Email__c'] = filter_input(INPUT_POST,'email',FILTER_SANITIZE_STRING);	//  reg email
	$applicantCollection['SFDC_Candidate_Alt_Email__c'] = filter_input(INPUT_POST,'a_email',FILTER_SANITIZE_STRING);	// alt email
	$applicantCollection['Preferred_Phone__c'] = filter_input(INPUT_POST,'preferred_phone',FILTER_SANITIZE_STRING);	// preferred phone
	$applicantCollection['Home_phone__c'] = filter_input(INPUT_POST,'home_phone',FILTER_SANITIZE_STRING);		// home phone
	$applicantCollection['Cell_phone__c'] = filter_input(INPUT_POST,'cell_phone',FILTER_SANITIZE_STRING);		// cell phone
	$applicantCollection['Work_ph__c'] = filter_input(INPUT_POST,'work_phone',FILTER_SANITIZE_STRING);		// work phone
	$applicantCollection['SFDC_Job__c'] = filter_input(INPUT_POST,'job_c',FILTER_SANITIZE_STRING);		// position applied for.  note hardcoded reference in career_apply_step2.php if this needs to be changed.
	$applicantCollection['Saw_our_ad_in__c'] = filter_input(INPUT_POST,'referer',FILTER_SANITIZE_STRING);		// saw ad in
//	$applicantCollection['Candidate_Status__c'] = 'Candidate Received';  // hardcoded in process_applicant.php


	// Note that two fields (candidate statement and address) will often have line breaks; these will vary by OS
	// we need to handle these using a separate process
	
	//$_POST['form_field_2'] = str_replace(array("\r\n", "\r", "\n"), "FCMREPLACE", $_POST['form_field_2']);
	//$_POST['form_field_11'] = str_replace(array("\r\n", "\r", "\n"), "FCMREPLACE", $_POST['form_field_11']);
	
	$FCMbridge1 = filter_input(INPUT_POST,'address',FILTER_SANITIZE_STRING);  // address
	$FCMbridge2 = filter_input(INPUT_POST,'statement',FILTER_SANITIZE_STRING);	// Applicant statement

	// now just make it a line break to preserve formatting. oddly, though, doesn't seem to work.
	$applicantCollection['SFDC_Candidate_Address__c'] = str_replace("FCMREPLACE","<br />",$FCMbridge1);  // address
	$applicantCollection['CandidateStatement__c'] = str_replace("FCMREPLACE","<br />",$FCMbridge2);  // Applicant statement
	
	
	$applicantCollectionName['Name'] = "Name";
	$applicantCollectionName['SFDC_Candidate_Address__c'] = "Street address";
	$applicantCollectionName['SFDC_Candidate_Email__c'] = "Email"; 		
	$applicantCollectionName['SFDC_Candidate_Alt_Email__c'] = "Alternate email";
	$applicantCollectionName['Preferred_Phone__c'] = "Preferred phone";
	$applicantCollectionName['Home_phone__c'] = "Home phone";
	$applicantCollectionName['Cell_phone__c'] = "Cell phone";
	$applicantCollectionName['Work_ph__c'] = "Work phone";
	$applicantCollectionName['SFDC_Job__c'] = "Position applied for";
	$applicantCollectionName['Saw_our_ad_in__c'] = "Saw our ad in";
	$applicantCollectionName['CandidateStatement__c'] = "Candidate statement";



	function sanitize($strtoclean)	{
	
		$strtoclean = utf8_decode($strtoclean);
		$strtoclean = strip_tags($strtoclean);
		$strtoclean = mysql_escape_string($strtoclean);
		$strtoclean = htmlspecialchars($strtoclean,ENT_COMPAT);
		// since magic_quotes_gpc is on, strip slashes
		$strtoclean = stripslashes($strtoclean);
		$strtoclean = str_replace('\\','',$strtoclean);
		// Dec 3 '07: re-encode input to try avoiding the UTF8 error returned by the SF API	
		$strtoclean = utf8_encode($strtoclean);

		return $strtoclean;
	}
	

	
	
	?>
