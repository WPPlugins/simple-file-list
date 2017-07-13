<?php  // Simple File List - ee-upload-display.php - v04.14.17 - mitchellbennis@gmail.com
	
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! wp_verify_nonce( $nonce2, 'ee_upload_display' ) ) exit; // Exit if nonce fails

// User Messaging	
if($eeMessages) { 
	$eeOutput .= '<div id="eeMessaging" class="updated">';
	$eeOutput .= eeMessageDisplay($eeMessages);
	$eeOutput .= '</div>';
}
	
if($eeErrors) { 
	$eeOutput .= '<div id="eeMessaging" class="error">';
	$eeOutput .= eeMessageDisplay($eeErrors);
	$eeOutput .= '</div>';
}

// Clear before the list is loaded
$eeErrors = array();
$eeMessages = array();
	
if ($eeSettings['eeUploadDir']) {
	
	// Decide where to submit the form
	if($eeAdmin) {
		$eeAction = site_url() . '/wp-admin/admin.php?page=ee-simple-file-list';
	} else {
		$eeAction = get_permalink();
	}
	
	$eeOutput .= '<form action="' .$eeAction . '" method="post" enctype="multipart/form-data">
		<input type="hidden" name="MAX_FILE_SIZE" value="' . (($eeSettings['ee_upload_max_filesize']*1024)*1024) . '" />
		<input type="hidden" name="eeUpload" value="TRUE" />';
		
	// Nonce is checked in ee_main_page_display() 		
	$eeOutput .= wp_nonce_field( 'ee-simple-file-list-upload', 'ee-simple-file-list-upload-nonce' );
	
	$eeOutput .= '<h2>Upload a File</h2>';
	    
	if($eeSettings['eeShowForm'] == 'Yes' AND !$eeAdmin) { eeUploadInfoForm();
		    
		    	$eeOutput .= '<label for="eefile">File:</label><br />'; // Matches the rest of the form.
		    
	}
	    
	$eeOutput .= '<input required type="file" name="eefile" id="eefile" />
		
		<p class="eeUploadSizeLimit alignright">' . $eeSettings['ee_upload_max_filesize'] . ' MB Upload Size Limit</p>
		
		<input type="submit" value="Upload the File" class="eeUploadButton" />
		
		<br class="eeClearFix" />
	
	</form>';
	
} else {
	$eeOutput .= 'No upload directory configured.';
	$eeLog[] = 'No upload directory configured.';
}