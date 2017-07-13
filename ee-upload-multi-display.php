<?php  // Simple File List - ee-upload-mult-display.php - v05.06.17 - mitchellbennis@gmail.com
	
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! wp_verify_nonce( $nonce2, 'ee_upload_display' ) ) exit('Nope'); // Exit if nonce fails

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
	
	$eeOutput .= '<form action="' . $eeAction . '" method="post" enctype="multipart/form-data" name="eeUploadForm" id="eeUploadForm">
		
		<input type="hidden" name="MAX_FILE_SIZE" value="' .(($eeSettings['ee_upload_max_filesize']*1024)*1024) . '" />
		<input type="hidden" name="eeUploadMulti" value="TRUE" />
		<input type="hidden" name="eeFileCount" value="" id="eeFileCount" />
		<input type="hidden" name="eeFileList" value="" id="eeFileList" />';
		
		// This is checked in ee_main_page_display() 
			
		$eeOutput .= wp_nonce_field( 'ee-simple-file-list-upload', 'ee-simple-file-list-upload-nonce' );
	
		$eeOutput .= '<h2>Upload Files</h2>';
		
		if($eeSettings['eeShowForm'] == 'Yes' AND !$eeAdmin) { eeUploadInfoForm(); }
	    
		$eeOutput .= '<input type="file" name="eeFileInput" id="eeFileInput" multiple />
		
		<br class="eeClearFix" />
		
		<p class="sfl_instuctions">Size Limit: ' . $eeSettings['ee_upload_max_filesize'] . ' MB per file. To select multiple files, hold down the Control key while choosing files (Command on Macs)</p>
		
		<script type="text/javascript">
		
			console.log("Simple File List - Multi-Uploader");
			
			var eeUploadFiles = document.querySelector("#eeFileInput"); // ???
			var eeFiles = "";
			var eeFileSet = new Array(); // Names
			var eeFileObjects = new Array(); // File objects
			var eeFileCount = 0; // How many to upload
			var eeUploaded = 0; // How many have uploaded
			var eeError = false; // Bad things have happened
			
			// Allowed file extentions
			var eeFormats = "' . $eeSettings['eeFormats'] . '";
			var eeFormatsArray = eeFormats.split(","); // An array of the things.
			
			jQuery(document).ready(function() {
			
				jQuery( "#eeUploadingNow" ).hide(); // Hide the spinner
				
				// File Queue Information
				document.getElementById("eeFileInput").addEventListener("change", function(){
				    
				    console.log("File Added");
				    
				    for(var i = 0; i<this.files.length; i++){
				        
				        var eeFile =  this.files[i];
				        
				        console.group("File # " + i);
				        console.log("Name: " + eeFile.name);
				        
				        // Validation
				        
				        // Size
				        console.log("Size: " + eeFile.size);
				        
				        if(eeFile.size > ' . (($eeSettings['ee_upload_max_filesize']*1024)*1024) . ') {
					        eeError = eeFile.name + " is too large to upload.";
				        }
				        
				        // Type
				        var eeExtension = eeFile.name.split(".").pop();
				        eeExtension = eeExtension.toLowerCase();
				        
				        if(eeFormatsArray.indexOf(eeExtension) == -1) {
					        eeError = "This file type (" + eeExtension + ") is not allowed.";
				        }
				        
				        console.log("Extension: " + eeExtension);
				        console.log("Type: " + eeFile.type);
				        
				        // Modified date
				        // console.log("Date: " + eeFile.lastModified);
				        
				        console.groupEnd();
				        
				        if(!eeError) { // If no errors
				        	
							eeFileObjects.push(eeFile); // Add object
							eeFileSet.push(eeFile.name); // Add name   
							
				        } else {
					        
					        alert(eeError); // Alert the user.
					        
					        eeError = false;
					        eeFile = false;
					        jQuery("#eeFileInput").val("");
					        return false;
				        }
				        
				    }
				    
				    eeFileCount = eeFileObjects.length; // Reset based on set
				    var eeFileQstring = JSON.stringify(eeFileSet);
				            
		            jQuery("#eeFileList").val(eeFileQstring); // Set the hidden inputs
					jQuery("#eeFileCount").val(eeFileCount); // The number of files
		            
		            console.log("#eeFileList  Set: " + eeFileQstring);
					console.log("#eeFileCount Set: " + eeFileCount);
				        
				    console.log("Files: " + eeFileSet);
				    console.log("Count: " + eeFileCount);
				    
				}, false);
				
				
		
			}); // END Ready Function
			
			
			
			// The Upload Queue Processor
			function eeUploadProcessor(eeFileObjects) {
				
				eeFileCount = eeFileObjects.length;
				
				if(eeFileCount) {
					
					// Remove button and replace with spinner
				    jQuery("#eeUploadGo" ).fadeOut( function(){ jQuery( "#eeUploadingNow" ).fadeIn(); } );
					// jQuery( "#eeUploadingNow" ).fadeIn();
				
					console.log("Uploading " + eeFileCount + " files...");
					
					for (var i = 0; i < eeFileCount; i++) { // Loop through and upload the files
						
						console.log("Processing File: " + eeFileObjects[i].name);
									            
			            eeUploadFile(eeFileObjects[i]); // Upload the file using the function below...
					}
				
				}		
			}
			
			
			// File Upload AJAX Call
			function eeUploadFile(eeFile) { // Pass in file object
			    
			    var eeUrl = "' . plugin_dir_url( __FILE__ ) . 'ee-upload-engine.php' . '";
			    var eeXhr = new XMLHttpRequest();
			    var eeFd = new FormData();
			    
			    console.log("Calling Engine: " + eeUrl);
			    console.log("Uploading: " + eeFile.name);
			    
			    eeXhr.open("POST", eeUrl, true);
			    
			    eeXhr.onreadystatechange = function() {
			        
			        if (eeXhr.readyState == 4) { // && eeXhr.status == 200 <-- Windows returns 404?
		            
		            	eeUploaded ++;
			            
			            console.log("File Uploaded (" + eeUploaded + " of " + eeFileCount + ")");
			            
						// Every thing ok, file uploaded
			            console.log("RESPONSE: " + eeXhr.responseText); // handle response.
			            
			            // Submit the Form
			            if(eeUploaded == eeFileCount) {
				            
				            if(eeXhr.responseText == "SUCCESS") {
				            
				            	console.log("--->>> SUBMITTING FORM ...");
				            	
				            	document.forms.eeUploadForm.submit(); // SUCCESS - Process the Form <<<----- FORM SUBMIT
								
					        } else {
						    	console.log("XHR Status: " + eeXhr.status);
						    	console.log("XHR State: " + eeXhr.readyState);
						    	
						    	var n = eeXhr.responseText.search("<"); // Error condition
						    	if(n === 0) {
							    	alert("Upload Error: " + eeFile.name);
							    	jQuery( "#eeUploadingNow" ).fadeOut();
							    }
							    return false;
					        }
				        }
			        
			        } else {
				    	console.log("XHR Status: " + eeXhr.status);
				    	console.log("XHR State: " + eeXhr.readyState);
				    	return false;
			        }
			    };
			    
			    // Pass the file name to the Upload Engine
			    eeFd.append("file", eeFile);
			    
			    // Security
			    ';
			    
			    $eeTimestamp = time();
			    $eeTimestampMD5 = md5('unique_salt' . $eeTimestamp);
			    
			    $eeOutput .= 'eeFd.append("timestamp", "' . $eeTimestamp . '"); 
			    eeFd.append("token", "' . $eeTimestampMD5 . '"); 
			        
			    // Send the AJAX request...
			    eeXhr.send(eeFd);
			}

			
			console.log("Waiting for files...");
			
		</script>
		
		<span id="eeUploadingNow"><img src="' . $eePluginURL . 'images/spinner.gif" width="32" height="32" alt="Spinner Icon" />Uploading</span>
		
		<button type="button" name="eeUploadGo" id="eeUploadGo" onclick="eeUploadProcessor(eeFileObjects);">Upload</button>
		
		<br class="eeClearFix" />
	
	</form>';
	
} else {
	$eeOutput .= 'No upload directory configured.';
	$eeLog[] = 'No upload directory configured.';
}