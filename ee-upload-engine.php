<?php // Simple File List - ee-upload-engine.php - v05.06.17 - mitchellbennis@gmail.com
	
// This script is accessed via AJAX by ee-upload-multi-display.php, okay?
	
ini_set("log_errors", 1);
error_reporting (E_ALL);
ini_set ('display_errors', FALSE);
ini_set("error_log", "ee-upload-error.log");

if(@$_GET['ping']) { exit('Hello'); } // Just checking we can reach this URL

if(empty($_FILES)) { 
	$eeError = "Missing Input";
	trigger_error($eeError, E_USER_ERROR);
	exit();
}

// Tie into Wordpress
define('WP_USE_THEMES', false);
$wordpress = getcwd() . '/../../../wp-blog-header.php';
require($wordpress);

$eeSettings = eeGetOptions(); // Get the plugin's options

if(!$eeSettings) {
	$eeError = "Cannot get plugin settings.";
	trigger_error($eeError, E_USER_ERROR);
	exit();
}

// Check size
$eeFileSize = $_FILES['file']['size'];
$ee_upload_max_filesize = $eeSettings['ee_upload_max_filesize']*1024*1024; // Convert MB to B

if($eeFileSize > $ee_upload_max_filesize) {
	$eeError = "File size is too large.";
	trigger_error($eeError, E_USER_ERROR);
	exit();
}

// Our file destination.
$eePath = ABSPATH . '/' . $eeSettings['eeUploadDir'] . '/';

// Go...
if(is_dir($eePath)) {
		
	$verifyToken = md5('unique_salt' . $_POST['timestamp']);
	
	if($_POST['token'] == $verifyToken) { // Security
		
		// Temp file
		$tempFile = $_FILES['file']['tmp_name'];
		
		// Clean up messy names
		
		$eeFileName = eeSanitizeFileName($_FILES['file']['name']);
		$eeTargetFile = $eePath . $eeFileName;
		
		// Get the file extension
		$eeDot = strrpos($eeTargetFile, '.');
		$eeExtension = strtolower(substr($eeTargetFile, $eeDot+1));
		
		$eeFormatsArray = array_map('trim', explode(',', $eeSettings['eeFormats']));
		
		if(!in_array($eeExtension, $eeFormatsArray)) {
			$eeError = 'File type not allowed: (' . $eeExtension . ')';
			trigger_error($eeError, E_USER_ERROR);
			exit($eeError);	
		}
		
		// trigger_error('Target File: ' . $eeTargetFile, E_USER_NOTICE);
		
		// Check if it already exists
		$eeTargetFile = eeCheckForDuplicateFile($eeTargetFile);
	
		// Save the file
		if(move_uploaded_file($tempFile, $eeTargetFile)) {
			
			if(!is_file($eeTargetFile)) {
				$eeError = 'Error - File System Error.'; // No good.
			} else {
				// SUCCESS
				exit('SUCCESS');
			}
		} else {
			$eeError = 'Cannot move the uploaded file: ' . $eeTargetFile;
		}
	
	} else {
		
		$eeError = 'Post Token does NOT match verification token';
	}
	
} else {
	$eeError = 'Path Not Found: ' . $eePath;
}

// Output
if($eeError) {
	trigger_error($eeError, E_USER_WARNING);
	exit();
}
	
?>