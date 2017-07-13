<?php
/**
 * @package Element Engage - Simple File List
 * @version 2.0.8
 * GPLv2 or later
 */
/*
Plugin Name: Simple File List
Plugin URI: http://elementengage.com/ee-simple-file-list/
Description: Simple File List - File Uploader and Sortable List
Author: Mitchell Bennis - Element Engage, LLC
Version: 2.0.8
Author URI: http://elementengage.com
*/

$eeSFL_Version = 'SFL-2.0.8';

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
global $wpdb;
if(!$wpdb) { exit('No DB'); }

// === Configuration ==================================================

$eeDevMode = FALSE; // Set false for normal use
// When TRUE, a general log file is written in the plugin folder.
// Activation and errors are always written to this file.

// Names
$eeAppName = 'Simple File List';
$eeUploadFolderName = 'simple-file-list'; // No spaces
$eePageTitle = 'Simple File List Administration';
$eePluginSlug = 'ee-simple-file-list';

$eeHomePath = ABSPATH . '/';
$eePluginPath = plugin_dir_path(__FILE__);
$eePluginURL = plugin_dir_url(__FILE__);

// Upload Vars
$eeAllowList = ''; // Show the file list
$eeFormats = '';
$eeAllowUploads = ''; // Show the upload form
$eeUploadDirDefault = 'wp-content/uploads/' . $eeUploadFolderName;	
$ee_max_file_size = 1; // MB
$ee_post_max_size = 1;
$eeFileOwner = 'No'; // Add username to file name?

// Variable Setup

// Ignore these
$eeExcluded = array('.', '..', "", basename($_SERVER['PHP_SELF']), '.php', '.htaccess', '.ftpquota', 'error_log');

// Email
$eeAdminTo = get_option('admin_email');
$eeAdminFrom = $eeAdminTo;

// Initialization
$eeUserAccess = 'edit_posts'; // Wordpress user level to see menu items.
$eeSettings = array();
$eeMessages = array();
$eeMsg = '';
$eeOutput = '';
$eeErrors = array();
$eeLog = array();
$eeAdmin = FALSE;
$eeSendOptions = FALSE; // BETA
$eePayerID = FALSE;

// Display Messages
$eeBackLink = 'https://elementengage.com/simple-file-list/';
$eeBackLinkTitle = $eeAppName . ' - Version ' . $eeSFL_Version;
$eeDisclaimer = 'IMPORTANT - Allowing the public to upload files to your web server comes with risk. Please double-check that you only use the file types you absolutely need and open each file submitted to you with great caution and intestinal fortitude.';


// Let's go! -----------------------------------------------------------

$eeLog[] = 'Simple File List is Running!';

// Are we in the admin area?
if(strpos($_SERVER['PHP_SELF'], 'wp-admin')) {
	$eeAdmin = TRUE;
}

function eeActivateSFL() {
	
	global $wpdb, $eeLog;
	
	$eeUploadDirDefault = 'wp-content/uploads/simple-file-list';
	
	$eeLog['ACTIVATE'] = 'Activating the plugin...';
		
	$eeLog[] = 'Checking the upload directory...';
		
	// Write the upload folder		
	if(eeUploadDirCheck($eeUploadDirDefault)) {
	
		// Check if options exist in the database
		if($wpdb->query("SELECT option_name FROM " . $wpdb->options . " WHERE option_name = 'eeSFL'") != 1) {
			
			$eeLog['INSTALLING'] = 'Database Installation...';
				
			// These are the default values...
			$eeSettings = 'eeAllowList=Yes|'; // To allow the front-side file list
			$eeSettings .= 'eeAllowUploads=Multi|'; // Can be Yes, No or Multi
			$eeSettings .= 'ee_upload_max_filesize=10|'; // Max upload size in MB
			$eeSettings .= 'eeFormats=gif,jpg,jpeg,bmp,png,tif,tiff,txt,eps,psd,ai,pdf,doc,xls,ppt,docx,xlsx,pptx,odt,ods,odp,odg,wav,wmv,wma,flv,3gp,avi,mov,mp4,m4v,mp3,webm,zip|'; // Only these types
			$eeSettings .= 'eeAdminTo=' . get_option('admin_email') . '|'; // Notification email
			$eeSettings .= 'eeFileOwner=No|'; // Adds username to filename if set to YES
			$eeSettings .= 'eeUploadDir=' . $eeUploadDirDefault . '|'; // The upload folder location - Default is /wp-content/uploads/simple-file-list
			$eeSettings .= 'eeSortList=Name|'; // List sort order, can be Name, Date, Size or Random.
			$eeSettings .= 'eeSortOrder=Decending|'; // Descending is the defualt
			$eeSettings .= 'eeShowForm=Not'; // The uploader info form
			
			// Add the new option_names
			$eeQuery = "INSERT INTO " . $wpdb->options . " (option_name, option_value) VALUES ('eeSFL', '$eeSettings')";
			
			$eeLog['Install Settings'] = $eeSettings;
								
			if($wpdb->query($eeQuery)) {
				$eeLog['INSTALLED'] = 'Simple File List is Installed!';
			} else {
				$eeLog[] = 'ERROR - Could not create database record.';
				$eeLog[] = @mysqli_error($wpdb);
			}
				
		} else {
			
			$eeLog[] = 'Database record already exists.';
			
		}
		
	} else {
		
		echo 'Default Upload Folder Cannot Be Created.<br />Please manually create a folder within your Wordpress Uploads folder named "simple-file-list".';
		
		echo '<pre>'; print_r($eeLog); echo '</pre>';
		
		return FALSE;
		
	} // ENDs install check
			
	eeSFL_WriteLogFile($eeLog);

}

register_activation_hook( __FILE__, 'eeActivateSFL' );


// Detect max upload size.
function eeDetectUploadLimit() {
	
	global $ee_max_file_size, $ee_post_max_size;
	
	$ee_upload_max_upload_size = substr(ini_get('upload_max_filesize'), 0, -1); // Strip off the "M".
	$ee_post_max_size = substr(ini_get('post_max_size'), 0, -1); // Strip off the "M".
	if ($ee_upload_max_upload_size <= $ee_post_max_size) { // Check which is smaller, upload size or post size.
		$ee_max_file_size = $ee_upload_max_upload_size;
	} else {
		$ee_max_file_size = $ee_post_max_size;
	}
}
eeDetectUploadLimit();


// Load stuff we need in the head
function ee_custom_admin_head() {

	global $eePluginURL;	
	
	echo '<link rel="stylesheet" type="text/css" href="' . $eePluginURL . 'css/eeStyling.css?ver=2">';
	// wp_enqueue_script('eeMainJS', $eePluginURL . 'scripts/eeMain.js');

}
add_action('admin_head', 'ee_custom_admin_head');


function eeEnqueue() {
	
	global $eePluginURL;
	
	// Register the style like this for a theme:
    wp_register_style( 'ee-plugin-css', $eePluginURL . 'css/eeStyling.css');
 
    // Enqueue the style:
    wp_enqueue_style('ee-plugin-css');
	
	// wp_enqueue_script('eeMainJS', $eePluginURL . 'scripts/eeMain.js');
}
add_action( 'wp_enqueue_scripts', 'eeEnqueue' );





// === FUNCTIONS ===================================


function eeUploadDirCheck($eeUploadDir) {
	
	global $eeLog, $eeErrors;
	
	if($eeUploadDir) {
		
		// Full Path
		$eeUploadDir = ABSPATH . '/' . $eeUploadDir;
		
		if(!@is_writable($eeUploadDir)) {
			
			$eeLog[] = 'No Upload Directory Found.';
			$eeLog[] = 'Creating Upload Directory ...';
			
			// Environment Detection
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			    $eeLog[] = 'Windows detected.';
			    mkdir($eeUploadDir); // Windows
			} else {
			    $eeLog[] = 'Linux detected.';
			    mkdir($eeUploadDir, 0755); // Linux - Need to set permissions
			}
			
			if(!@is_writable($eeUploadDir)) {
				$message = 'ERROR - I could not create the upload directory: ' . $eeUploadDir;
				$eeLog[] = $message;
				return FALSE;
			
			} else {
				
				$eeLog[] = 'Upload Path: ' . $eeUploadDir;
				return TRUE;
			}
		} else {
			$eeLog[] = 'Upload Path: ' . $eeUploadDir;
			return TRUE;
		}
		
	} else {
		$eeLog[] = 'ERROR - No upload directory defined.';
		return FALSE;
	}
}


// Check if good URL
function eeUrlExists($eeFileURL) {
  
  $parts=parse_url($eeFileURL);
  if(!$parts) return false; /* the URL was seriously wrong */
     
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $eeFileURL);
 
  /* set the user agent - might help, doesn't hurt */
  curl_setopt($ch, CURLOPT_USERAGENT, 'EE-SFL: URL Validation Script - ' . $_SERVER['HTTP_HOST']);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
   
  /* try to follow redirects */
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
 
  /* timeout after the specified number of seconds. assuming that this script runs
    on a server, 20 seconds should be plenty of time to verify a valid URL.  */
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
  curl_setopt($ch, CURLOPT_TIMEOUT, 20);
         
  /* don't download the page, just the header (much faster in this case) */
  curl_setopt($ch, CURLOPT_NOBODY, true);
  curl_setopt($ch, CURLOPT_HEADER, true);
         
  /* handle HTTPS links */
  if($parts['scheme']=='https'){
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,  2);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  }
     
  $response = curl_exec($ch);
  curl_close($ch);
   
  /*  get the status code from HTTP headers */
  if(preg_match('/HTTP\/1\.\d+\s+(\d+)/', $response, $matches)){
    $code=intval($matches[1]);
  } else {
    return false;
  };
 
  /* see if code indicates success */
  return (($code>=200) && ($code<400));  
}


function eeGetURLReturn($url) {
	$ch = curl_init();
	$timeout = 5;
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
	$data = curl_exec($ch);
	curl_close($ch);
	if(is_string($data)) { return $data; }
		else { return FALSE; }
}


// Shortcode Setup
function eeSFLshortcode( $atts ) {
    
    // Usage: [eeSFL]
    $eeOutput = ee_main_page_display();
    
    return $eeOutput;
}
add_shortcode( 'eeSFL', 'eeSFLshortcode' );



// Make sure user can access this stuff
function eeCheckUser() {
	global $eeUserAccess;
	require_once(ABSPATH . "wp-includes/pluggable.php");
	if (current_user_can($eeUserAccess)) {
		return TRUE;
	} else {
		return FALSE;
	}	
}


// Problem Display / Error reporting
function eeMessageDisplay($eeMsg) {
	
	global $eeLog, $eeAdmin;
	
	$eeOutput = '';
	
	$eeLog[] = 'Displaying User Messages...';
	$eeLog[] = $eeMsg;
	
	if(is_array($eeMsg)) {
		
		if(!$eeAdmin) { $eeOutput .= '<div id="eeMessageDisplay">'; }
		
		$eeOutput .= '<ul>'; // Loop through $eeMessages array
		foreach($eeMsg as $key => $value) { 
			if(is_array($value)) {
				foreach ($value as $value2) {
					$eeOutput .= "<li>$value2</li>\n";
				}
			} else {
				$eeOutput .= "<li>$value</li>\n";
			}
		}
		$eeOutput .= "</ul>\n";
		
		if(!$eeAdmin) { $eeOutput .= '</div>'; }
		
		return $eeOutput;
		
	} else {
		$eeLog[] = 'ERROR: Bad Message Array';
		return FALSE;
	}

}

// EMAIL NOTIFICATION
function eeNoticeEmail($eeMsg) {

	global $eeSettings, $eeMessages, $eeAdminFrom, $eeLog;
	
	$eeReplyTo = $eeAdminFrom;
	
	if($eeSettings['eeAdminTo']) {
		$eeAdminTo = $eeSettings['eeAdminTo'];
	}
	
	if($eeAdminTo) {
		
		$eeBody = "Greetings,\n\n";
		
		if(is_array($eeMsg)) {
			foreach ($eeMsg as $value) {
				if(is_array($value)) {
					foreach ($value as $value2) {
						$eeBody .= $value2 . "\n\n";
					}
				} else {
					$eeBody .= $value . "\n\n";
				}
			}
		} else {
			$eeBody .= $eeMsg . "\n\n";
		}
		
		// Get Form Input?
		if($eeSettings['eeShowForm'] == 'Yes') {
			
			$eeName = substr(filter_var(@$_POST['eeName'], FILTER_SANITIZE_STRING), 0, 64);
			$eeName = strip_tags($eeName);
			$eeBody .= 'Uploaded By: ' . ucwords($eeName) . " - ";
			
			$eeEmail = substr(filter_var(@$_POST['eeEmail'], FILTER_VALIDATE_EMAIL), 0, 128);
			$eeBody .= strtolower($eeEmail) . "\n\n";
			$eeReplyTo = $eeName . ' <' . $eeEmail . '>';
			
			$eeNotes = substr(filter_var(@$_POST['eeNotes'], FILTER_SANITIZE_STRING), 0, 5012);
			$eeNotes = strip_tags($eeNotes);
			$eeBody .= $eeNotes . "\n\n";

		}
		
		$eeBody .= "\n\n----------------------\n\nVia: Simple File List, located at " . $_SERVER['HTTP_HOST'];
		
		$eeHeaders = "From: Simple File List <$eeAdminFrom>\nReturn-Path: $eeAdminFrom\nReply-To: $eeReplyTo\n";
		$eeSubject = "File Upload Notice";
		
		$eeLog[] = 'Sending Notice...';
		$eeLog[] = $eeAdminTo;
		$eeLog[] = $eeSubject;
		$eeLog[] = $eeBody;
		$eeLog[] = $eeHeaders;
	
		if(mail($eeAdminTo, $eeSubject, $eeBody, $eeHeaders)) { // Email the message or error report
			$eeLog[] = 'Email Message Sent!';
			return TRUE;
		} else {
			$eeErrors[] = 'ERROR: Email could not be sent.';
		}	
	
	} else {
		$eeMessages['No TO address. No notifications sent.'];
		return FALSE;
	}		
}

// Size formatting
function eeBytesToSize($bytes, $precision = 2) {  
    
    $kilobyte = 1024;
    $megabyte = $kilobyte * 1024;
    $gigabyte = $megabyte * 1024;
    $terabyte = $gigabyte * 1024;
   
    if (($bytes >= 0) && ($bytes < $kilobyte)) {
        return $bytes . ' B';
 
    } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
        return round($bytes / $kilobyte, $precision) . ' KB';
 
    } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
        return round($bytes / $megabyte, $precision) . ' MB';
 
    } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
        return round($bytes / $gigabyte, $precision) . ' GB';
 
    } elseif ($bytes >= $terabyte) {
        return round($bytes / $terabyte, $precision) . ' TB';
    } else {
        return $bytes . ' B';
    }
}


function eeSanitizeFileName($eeFileName) {
	
	$eeFileName = strip_tags($eeFileName); 
    $eeFileName = preg_replace('/[\r\n\t ]+/', ' ', $eeFileName);
    $eeFileName = preg_replace('/[\"\*\/\:\<\>\?\'\|]+/', ' ', $eeFileName);
    // $eeFileName = strtolower($eeFileName);
    $eeFileName = html_entity_decode( $eeFileName, ENT_QUOTES, "utf-8" );
    $eeFileName = htmlentities($eeFileName, ENT_QUOTES, "utf-8");
    $eeFileName = preg_replace("/(&)([a-z])([a-z]+;)/i", '$2', $eeFileName);
    $eeFileName = str_replace(' ', '-', $eeFileName);
    // $eeFileName = rawurlencode($eeFileName);
    $eeFileName = str_replace('%', '-', $eeFileName);
    return $eeFileName;
}

// Check if a file already exists, then number it if so.
function eeCheckForDuplicateFile($eeTargetFile) {
	
	if(is_file($eeTargetFile)) {
		
		// Get the file extension
		$eeDot = strrpos($eeTargetFile, '.');
		$eeExtension = strtolower(substr($eeTargetFile, $eeDot+1));
		
		// Append a version to the name
		$eeFilePath = substr($eeTargetFile, 0, $eeDot);
		
		$eeCopyLimit = 1000; // Copy limit
		
		for ($i = 1; $i <= $eeCopyLimit; $i++) {
			
			$eeTargetFile = $eeFilePath . '_(' . $i . ').' . $eeExtension; // Indicate the copy number
			
			if(!is_file($eeTargetFile)) { break; }
		}							
	}
		
	return 	$eeTargetFile;

}


// Single File Uploading - No upload or file data is stored in the database.
function eeUpload($eeSettings) {
	
	global $eeAdmin, $eeErrors, $eeLog;
	
	$eeUploadPath = ABSPATH . '/' . $eeSettings['eeUploadDir'] . '/'; 
	
	// Get the original file extension
	$fileName = strtolower(basename($_FILES['eefile']['name']));  // Read original name of the uploaded file.
	$ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
	
	// Remove white space from array values
	$eeFormatsArray = array_map('trim', explode(',', $eeSettings['eeFormats']));
	
	// Only allow allowed files, ah?
	if (in_array($ext, $eeFormatsArray)) {
	
		$eeLog[] = 'Beginning the upload...';
		
		// File Naming
		$fileName = pathinfo($_FILES['eefile']['name'], PATHINFO_FILENAME);  
		
		
		// $time = date('m-d-Y--G:i:s');
		// $fileName = str_replace(' ', '_', $fileName); // Replace any spaces with underscores
		$fileName = eeSanitizeFileName($fileName); // Get rid of problem characters for the file name.
		
		// Add username if logged in.
		$currentUser = wp_get_current_user();
		if(is_object($currentUser) AND $eeSettings['eeFileOwner'] == 'Yes') {
			$fileName .= '_' . $currentUser->user_login;
		}
	
		$newFile = $fileName . "." . $ext;  // Assemble new file name and extension.
		$eeTargetFile = $eeUploadPath . basename($newFile); // Define where the file will go.
		
		$eeTargetFile = eeCheckForDuplicateFile($eeTargetFile);
	
		if (@move_uploaded_file($_FILES['eefile']['tmp_name'], $eeTargetFile)) { // Move the file to the final destination
				
			$message = "A new file has been uploaded...\n\n" . 
				'http://' . $_SERVER['HTTP_HOST'] . '/' . $eeSettings['eeUploadDir'] . '/' . $newFile . 
					"\n\nSize: " . eeBytesToSize(filesize($eeUploadPath . $newFile));
			
			$eeMessages[] = $message;
			$eeLog[] = $message;
			eeNoticeEmail($message);
			
			if(!$eeAdmin) {
				?><script>alert('File Upload Complete');</script><?php
			}
			
			return TRUE;
		
		} else { // Upload Problem
			$eeErrors[] = 'No file was uploaded';
			
			switch ($_FILES['eefile']['error']) {
				case 1:
					// The file exceeds the upload_max_filesize setting in php.ini
					$eeErrors[] = 'File Too Large - Please resize your file to meet the file size limit.';
					break;
				case 2:
					// The file exceeds the MAX_FILE_SIZE setting in the HTML form
					$eeErrors[] = 'File Too Large - Please resize your file to meet the file size limits.';
					break;
				case 3:
					// The file was only partially uploaded
					$eeErrors[] = 'Upload Interrupted - Please back up and try again.';
					break;
				case 4:
					// No file was uploaded
					$eeErrors[] = 'No File was Uploaded - Please back up and try again.';
					break;
			}
			
			?><script>alert('File Upload FAILED!');</script><?php
			
		}
	} else {
		$eeErrors[] = 'Sorry, the file type being uploaded is not accepted by this website.';
		if(!$ext) { $ext = '(Unknown)'; }
		$eeErrors[] = "File Name: " . $_FILES['eefile']['name'];
		$eeErrors[] = "Filetype: " . $ext;
		
	}
	
	return FALSE;
			
} // END File Upload 


function eeGetOptions() {
	
	global $wpdb, $eeSettings, $eeLog;
	
	// $eeSettings = array();
	
	// Get current info...
	$eeQuery = "SELECT option_value FROM " . $wpdb->options . " WHERE option_name = 'eeSFL'";
	
	// Run the query
	$eeResult = $wpdb->get_results($eeQuery, ARRAY_N);
	
	if($eeResult) {
		
		// Get the result
		$eeOptions = $eeResult[0][0];
		
		$eeSettings = explode('|', $eeOptions);
		
		// Set our variables
		$eeAllowList = explode('=', $eeSettings[0]);
		$eeSettings['eeAllowList'] = $eeAllowList[1];
		
		$eeAllowUploads = explode('=', $eeSettings[1]);
		$eeSettings['eeAllowUploads'] = $eeAllowUploads[1];
		
		$ee_upload_max_filesize = explode('=', $eeSettings[2]);
		$eeSettings['ee_upload_max_filesize'] = $ee_upload_max_filesize[1];
		
		$eeFormats = explode('=', $eeSettings[3]);
		$eeSettings['eeFormats'] = $eeFormats[1];
		
		$eeAdminTo = explode('=', $eeSettings[4]);
		$eeSettings['eeAdminTo'] = $eeAdminTo[1];
	
		$eeFileOwner = explode('=', $eeSettings[5]);
		$eeSettings['eeFileOwner'] = $eeFileOwner[1];
	
		$eeUploadDir = explode('=', $eeSettings[6]);
		$eeSettings['eeUploadDir'] = $eeUploadDir[1];
		
		$eeSortList = explode('=', $eeSettings[7]);
		$eeSettings['eeSortList'] = $eeSortList[1];
		
		$eeSortOrder = explode('=', $eeSettings[8]);
		$eeSettings['eeSortOrder'] = $eeSortOrder[1];
	
		$eeShowForm = explode('=', $eeSettings[9]);
		$eeSettings['eeShowForm'] = $eeShowForm[1];
		
		// $eeLog['Config'] = $eeSettings;
		
		return $eeSettings;
	
	} else {
		
		return FALSE;
	}
}


function eeUploadInfoForm() {
	
	global $eeOutput;
	
	$eeOutput .= '<h4>Your Information</h4>
		
		<label for="eeName">Name:</label><input placeholder="(required)" required type="text" name="eeName" value="" id="eeName" size="64" maxlength="64" /> 
		
		<label for="eeEmail">Email:</label><input placeholder="(required)" required type="email" name="eeEmail" value="" id="eeEmail" size="64" maxlength="128" />
		
		<label for="eeNotes">Notes:</label><textarea name="eeNotes" id="eeNotes" rows="5" cols="64" maxlength="5012"></textarea>';

}


function eeSFL_WriteLogFile($eeLog) {
	
	if($eeLog) {
		
		$eeLogFile = plugin_dir_path( __FILE__ ) . 'Simple-File-List-Log.txt';
		
		if($handle = @fopen($eeLogFile, "a+")) {
			
			if(@is_writable($eeLogFile)) {
			    
				fwrite($handle, date("Y-m-d H:i:s") . "\n");
			    
			    foreach($eeLog as $key => $logEntry){
			    
			    	if(is_array($logEntry)) { $logEntry = implode("\n", $logEntry); }
			    	
			    	fwrite($handle, '(' . $key . ') ' . $logEntry . "\n");
			    }
			    	
			    fwrite($handle, "\n\n\n---------------------------------------\n\n\n"); // Separator
			
			    fclose($handle);
			    
			    return TRUE;
			 
			} else {
			    return FALSE;
			}
		} else {
			return FALSE;
		}
	} else {
		return FALSE;
	}
}

// END FUNCTIONS ---------------


// === ADMIN PAGE SETUP ========================================================


// Register the function using the admin_menu action hook.
add_action( 'admin_menu', 'ee_plugin_menu' );

// Create a function that contains the menu-building code.
function ee_plugin_menu() {
	global $eePageTitle, $eeAppName, $eeUserAccess;
	add_menu_page( $eePageTitle, $eeAppName, $eeUserAccess, 'ee-simple-file-list', 'ee_main_page_display', 'dashicons-index-card');
	add_submenu_page( 'ee-simple-file-list', $eePageTitle, 'Settings', 'edit_users', 'ee-simple-file-list-admin', 'ee_admin_page_display');
}




// Create the HTML output for the page (screen) displayed when the menu item is clicked.
function ee_main_page_display() {
	
	eeCheckUser();
	
	global $wpdb, $eeSFL_Version, $eeLog, $eeDevMode, $eeErrors, $eeMessages, $eeAppName, $eeLogFile, $eeOutput, 
		$eeSettings, $eeSendOptions, $eePluginPath, $eePluginURL, $eePluginSlug, $eeAdmin, $eePayerID;
		
	// Create some nonces to check on the included pages.
	$nonce1 = wp_create_nonce('ee_main_page_display');
	$nonce2 = wp_create_nonce('ee_upload_display');
	
	$eeSettings = eeGetOptions();
	
	if(!empty($eeSettings)) {
		
		// Check for upload job...
			
		// Single Uploader - Check for upload POST and Nonce
		if(@$_POST['eeUpload'] AND check_admin_referer( 'ee-simple-file-list-upload', 'ee-simple-file-list-upload-nonce')) {
			
			// Here is where we upload the file
			if(eeUpload($eeSettings)) {
				$eeMessages[] = "File Upload Complete.";
			}
					
		// Multi Uploader - Check for upload POST and Nonce 
		} elseif(@$_POST['eeUploadMulti'] AND check_admin_referer( 'ee-simple-file-list-upload', 'ee-simple-file-list-upload-nonce')) {
			
			// File have been uploaded, now we send the notice email
			if($_POST['eeFileCount']) {
				
				// echo '<pre>'; print_r($_POST); echo '</pre>';
				
				// Validation
				$eeFileCount = filter_var(@$_POST['eeFileCount'], FILTER_VALIDATE_INT);
				$eeFileList = stripslashes(@$_POST['eeFileList']);
				$eeFileArray = json_decode($eeFileList);
				
				$eeMsg = 'You should know that ';
				if($eeFileCount > 1) { $eeMsg .= $eeFileCount . " files have"; } else { $eeMsg .= "a file has"; }
				$eeMsg .= " been uploaded to your website.\n\n";
				
				if(is_array($eeFileArray)) {
					
					foreach($eeFileArray as $eeFile){
						$eeMsg .= $eeFile . "\nhttp://" . $_SERVER['HTTP_HOST'] . '/' . $eeSettings['eeUploadDir'] . '/' . $eeFile . 
							"\n(" . eeBytesToSize(filesize(ABSPATH . '/' . $eeSettings['eeUploadDir'] . '/' . $eeFile)) . ")\n\n\n";
					}
						
					$eeMessages[] = "File Upload Complete";
					
					eeNoticeEmail($eeMsg);
					
					?><script>alert('File Upload Complete');</script><?php
					
				} else {
					$eeLog[] = 'ERROR: Cannot send mail. Bad file array';
					$eeLog['POST'] = $_POST;
				}
			}
		}
	}
	
	// Begin Output
	$eeOutput = '<div id="eeSFL"';
	if($eeAdmin) { $eeOutput .= 'class="eeAdminEntry"'; }
	$eeOutput .= '>';
	
	// Front Side Display
	if(!$eeAdmin AND $eeSettings['eeAllowUploads'] != 'No') {
		
		if($eeSettings['eeAllowUploads'] == 'Multi') {
			
			include($eePluginPath . '/ee-upload-multi-display.php'); // Show javascript multi-file uploader
			
		} elseif($eeSettings['eeAllowUploads'] == 'Yes') {
			
			include($eePluginPath . '/ee-upload-display.php'); // Show basic HTML uploader
		}		
	}
	
	// List Display
	if(!$eeAdmin AND $eeSettings['eeAllowList'] == 'Yes') {
		include($eePluginPath . '/ee-list-display.php');
	}
	
	
	
	// Admin Display
	if($eeAdmin) {
		$eeOutput .= '<h1>' . $eeAppName . '</h1>';
		
		if($eeSettings['eeAllowUploads'] == 'Multi') {
			include($eePluginPath . '/ee-upload-multi-display.php'); // Show javascript multi-file uploader
		} elseif($eeSettings['eeAllowUploads'] == 'Yes') {
			include($eePluginPath . '/ee-upload-display.php'); // Show basic HTML uploader
		}
		
		include($eePluginPath . 'ee-list-display.php');
	}
	
	$eeOutput .= '</div>';
	
	if($eeAdmin) { include('ee-donations.php'); }
	
	
	// Error Recording
	if($eeErrors) {
		$eeLog[] = 'Uh oh, bad things happened...';
		$eeLog[] = $eeErrors;
		eeNoticeEmail($eeErrors);
	}
	
	// Development...
	if($eeErrors OR $eeDevMode) { 
		
		if($eeMessages) { $eeLog[] = $eeMessages; }
		if($eeErrors) { $eeLog[] = $eeErrors; }
		eeSFL_WriteLogFile($eeLog);
		
	}
	
	if($eeAdmin) { // Back-side
		
		echo $eeOutput;
		
		// Woo Hoo! Open new donation window! <----- BETA
		if($eeSendOptions) {
			echo '<script>window.open("https://elementengage.com/paypal/index.php' . $eeSendOptions . '");</script>';
		}
		
	} else {
		
		return $eeOutput; // Front-side - Send the display to the shortcode
	}
	
} // ENDs Main Page Display



// Admin Only Admin Page for Administrators
function ee_admin_page_display() {

	eeCheckUser();
	global $wpdb, $eeLog, $eeDevMode, $eeOutput, $eeErrors, $eeMessages, $eeAppName, $eeUploadLink, $eeUploadDirDefault, 
		$eePluginPath, $ee_post_max_size, $eeFormats, $eeAdmin, $eeAdminTo, $eeDisclaimer, $eeFileOwner,
			$eeBackLink, $eeBackLinkTitle, $eePluginSlug, $eeSendOptions, $eePayerID;
	
	$eeConfirm = FALSE;
	
	// Create a nonce to check on the included pages.
	$nonce = wp_create_nonce('ee_list_settings');
	
	echo '<h1>Simple File List Settings</h1>';	
	
	include($eePluginPath . '/ee-list-settings.php');
	
	// Woo Hoo! Open new donation window! <----- BETA
	if($eeSendOptions) {
		echo '<script>window.open("https://elementengage.com/paypal/index.php' . $eeSendOptions . '");</script>';
	}
	
	if($eeErrors OR $eeDevMode) { 
		
		if($eeMessages) { $eeLog[] = $eeMessages; }
		if($eeErrors) { $eeLog[] = $eeErrors; }
		eeSFL_WriteLogFile($eeLog);
		
	}
}
?>