<?php // Simple File List - ee-list-settings.php - v05.06.17 - mitchellbennis@gmail.com
	
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! wp_verify_nonce( $nonce, 'ee_list_settings' ) ) exit; // Exit if nonce fails
	
// Check for POST and Nonce
if(@$_POST['eePost'] AND check_admin_referer( 'ee-simple-file-list-settings', 'ee-simple-file-list-settings-nonce')) {
		
	// Store our settings in the options table as a pipped string.
	// We'll turn this into an array on the other side.
	
	// Data validation and sanitizing much improved - 11.17.15
	
	// Only accept Yes as the answer string.
	$eeSettings = 'eeAllowList=';
	if($_POST['eeAllowList'] == 'Yes') { $eeSettings .= 'Yes'; } else { $eeSettings .= 'No'; }
	$eeSettings .= '|';
	
	// Only accept Yes as the answer string. Gotta stay positive!
	$eeSettings .= 'eeAllowUploads=';
	
	if($_POST['eeAllowUploads'] == 'Yes') { $eeSettings .= 'Yes'; } 
	
	elseif($_POST['eeAllowUploads'] == 'Multi') { 
			
		if(eeUrlExists( plugin_dir_url( __FILE__ ) . 'ee-upload-engine.php?ping=true') ) {
			$eeSettings .= 'Multi';
		
		} else { 
			$eeSettings .= 'Yes'; // Revert
			$eeErrors[] = 'Multi-upload engine cannot be reached. Reverting to single mode.';
			$eeErrors[] = plugin_dir_url( __FILE__ ) . 'ee-upload-engine.php?ping=true';
		}
	} 
	else { $eeSettings .= 'No'; }
	$eeSettings .= '|';
	
	// This must be a number
	if(@$_POST['ee_upload_max_filesize']) {
		$ee_upload_max_filesize = (int) $_POST['ee_upload_max_filesize'];
		// Can't be more than the system allows.
		if(!$ee_upload_max_filesize OR $ee_upload_max_filesize > $ee_post_max_size) { $ee_upload_max_filesize = $ee_post_max_size; }
		$eeSettings .= 'ee_upload_max_filesize=' . $ee_upload_max_filesize . '|';
	} else {
		$eeSettings .= 'ee_upload_max_filesize=1|';
	}
	
	
	// Strip all but what we need for the comma list of file extensions
	if(@$_POST['eeFormats']) {
		$eeSettings .= 'eeFormats=' . preg_replace("/[^a-z0-9,]/i", "", $_POST['eeFormats']) . '|';
	} else {
		$eeErrors[] = 'You must have at least one approved file format.';
		$eeSettings .= 'eeFormats=jpg|';
	}
	
	// Need a good email. It's okay if there isn't one. No messages will be sent.
	// Relying on input[type=email] to catch user input errors.
	
	if(@$_POST['eeAdminTo']) {
		
		$eeTo = $_POST['eeAdminTo'];
		
		if(strpos($eeTo, ',')) { // Multiple Addresses
		
			$eeAddresses = explode(',', $eeTo); // Make array
			
			$eeAddressesString = '';
			
			foreach($eeAddresses as $add){
				
				$add = trim($add);
				
				if(filter_var($add, FILTER_VALIDATE_EMAIL)) {
			
					$eeAddressesString .= $add . ',';
				} else {
					$eeErrors[] = $add . ' is not a valid email address.';
				}
			}
			
			$eeAddressesString = substr($eeAddressesString, 0, -1); // Remove last comma
			
			$eeSettings .= 'eeAdminTo=' . $eeAddressesString;
			
		
		} elseif(filter_var($_POST['eeAdminTo'], FILTER_SANITIZE_EMAIL)) { // Only one address
			
			$add = $_POST['eeAdminTo'];
			
			if(filter_var($add, FILTER_VALIDATE_EMAIL)) {
				$eeSettings .= 'eeAdminTo=' . $add;
			} else {
				$eeErrors[] = $add . ' is not a valid email address.';
			}
			
		} else {
			
			$eeSettings .= "eeAdminTo="; // Anything but a good email gets null.
		}
	}
	
	// Add username to filename - Only accept Yes as the answer string.
	$eeSettings .= '|eeFileOwner=';
	if($_POST['eeFileOwner'] == 'Yes') { $eeSettings .= 'Yes'; } else { $eeSettings .= 'No'; }
	
	// Upload folder
	$eeSettings .= '|eeUploadDir=';
	if(@$_POST['eeUploadDir']) {
		$eeUploadDir = filter_var($_POST['eeUploadDir'], FILTER_SANITIZE_STRING);
		
		// Get rid of leading slash
		if(strpos($eeUploadDir, '/') === 0) {
			$eeUploadDir = substr($eeUploadDir, 1);
		}
		
		if(!eeUploadDirCheck($eeUploadDir)) {
			$eeErrors[] = 'Cannot create the file directory. Reverting to default.';
			$eeUploadDir = $eeUploadDirDefault;
		}	
	} else {
		$eeErrors[] = 'The upload directory cannot be left blank. Reverting to default.';
		$eeUploadDir = $eeUploadDirDefault;
	}
	$eeSettings .= $eeUploadDir; // Use the default if left blank.
	
	
	$eeSettings .= '|eeSortList=';
	if(@$_POST['eeSortList']) {
		$eeSortList = filter_var($_POST['eeSortList'], FILTER_SANITIZE_STRING);
		$eeSettings .= $eeSortList;
	}
	
	$eeSettings .= '|eeSortOrder=';
	if(@$_POST['eeSortOrder']) {
		$eeSortOrder = filter_var($_POST['eeSortOrder'], FILTER_SANITIZE_STRING);
		$eeSettings .= $eeSortOrder;
	}
	
	$eeSettings .= '|eeShowForm=';
	$eeShowForm = 'No';
	if(@$_POST['eeShowForm']) {
		$eeShowForm = filter_var($_POST['eeShowForm'], FILTER_SANITIZE_STRING);
		$eeSettings .= $eeShowForm;
	}
	
	
	$eeLog[] = 'New Settings...';
	$eeLog[] = $eeSettings;
	
	// Build the query
	$eeQuery = "UPDATE " . $wpdb->options . " SET option_value = '" . $eeSettings . "' WHERE option_name = 'eeSFL'";
	
	// Run the query
	if($wpdb->query($eeQuery)) {
		$eeConfirm = 'Simple File List Settings Have Been Updated!';
		$eeLog[] = $eeConfirm;
	} else {
		$eeLog[] = @mysqli_error($wpdb);
	}
}


// Get current info...
$eeQuery = "SELECT option_value FROM " . $wpdb->options . " WHERE option_name = 'eeSFL'";

// Run the query
$eeResult = $wpdb->get_results($eeQuery, ARRAY_N);

if($eeResult) {
	
	// Get the result
	$eeSettings = $eeResult[0][0];
	$eeLog[] = $eeSettings;
	$eeSettings = explode('|', $eeSettings);
	
	// Set our variables
	$eeAllowList = explode('=', $eeSettings[0]);
	$eeAllowList = $eeAllowList[1];
	
	$eeAllowUploads = explode('=', $eeSettings[1]);
	$eeAllowUploads = $eeAllowUploads[1];
	
	$ee_upload_max_filesize = explode('=', $eeSettings[2]);
	$ee_upload_max_filesize = $ee_upload_max_filesize[1];
	
	$eeFormats = explode('=', $eeSettings[3]);
	$eeFormats = $eeFormats[1];
	
	$eeAdminTo = explode('=', $eeSettings[4]);
	$eeAdminTo = $eeAdminTo[1];
	
	$eeFileOwner = explode('=', $eeSettings[5]);
	$eeFileOwner = $eeFileOwner[1];
	
	$eeUploadDir = explode('=', $eeSettings[6]);
	$eeUploadDir = $eeUploadDir[1];
	
	$eeSortList = explode('=', $eeSettings[7]);
	$eeSortList = $eeSortList[1];
	
	$eeSortOrder = explode('=', $eeSettings[8]);
	$eeSortOrder = $eeSortOrder[1];
	
	$eeShowForm = explode('=', $eeSettings[9]);
	$eeShowForm = $eeShowForm[1];

}
	
?>

<div class="eeAdminEntry">
	
	<?php 
		
		if($eeErrors) { 
			$eeOutput = '<div class="error"><p>';
			$eeOutput .= eeMessageDisplay($eeErrors);
			$eeOutput .= '</p></div>';
			echo $eeOutput;
		}
		
		if($eeConfirm) { echo '<div class="updated"><p>' . $eeConfirm . '</p></div>'; }
	
	?>
	
	<p class="eeAlignRight"><a href="<?php echo plugin_dir_url( __FILE__ ) . '/readme.txt' ?>">See What's New in Version 2</a></p>
	
	<p>USAGE: Place this bit of shortcode in any Post or Page that you would like the plugin to appear: <strong><em>[eeSFL]</em></strong></p>
	
	<form action="<?php echo $_SERVER['PHP_SELF'] . '?page=ee-simple-file-list-admin'; ?>" method="post" id="eeSFL_Settings">
		<input type="hidden" name="eePost" value="TRUE" />	
		
		<?php wp_nonce_field( 'ee-simple-file-list-settings', 'ee-simple-file-list-settings-nonce' ); ?>
		
		<fieldset>
		
			<input type="submit" name="submit" id="submit" value="SAVE" class="eeAlignRight" />
		
			<h2>The List</h2>
			
			<span>Show the File List?</span><label for="eeListYes" class="eeRadioLabel">Yes</label><input type="radio" name="eeAllowList" value="Yes" id="eeListYes" <?php if($eeAllowList == 'Yes') { echo 'checked'; } ?> />
				<label for="eeListNo" class="eeRadioLabel">No</label><input type="radio" name="eeAllowList" value="" id="eeListNo" <?php if($eeAllowList != 'Yes') { echo 'checked'; } ?> />
				<br class="eeClearFix" />
				<div class="eeNote">Affects the front-end file list only. You can use the uploader without showing the file list.</div>
			<br class="eeClearFix" />
			
			
			<label for="eeSortList">Sort By:</label><select name="eeSortList" id="eeSortList" class="">
					<option value="Name" <?php if($eeSortList == 'Name') { echo 'selected'; } ?>>File Name</option>
					<option value="Date" <?php if($eeSortList == 'Date') { echo 'selected'; } ?>>File Date</option>
					<option value="Size" <?php if($eeSortList == 'Size') { echo 'selected'; } ?>>File Size</option>
					<option value="Random" <?php if($eeSortList == 'Random') { echo 'selected'; } ?>>Random</option>
				</select> 
				
			<br class="eeClearFix" />
				
			<label for="eeSortOrder">Sort Order:</label>
			<input type="checkbox" name="eeSortOrder" value="Descending" id="eeSortOrder" <?php if($eeSortOrder == 'Descending') { echo 'checked="checked"'; } ?> /> <p>&darr; Descending</p>
			<div class="eeNote">The list is sorted Ascending by default: A to Z, Small to Large, Old to New<br />
				Check this box to reverse the default sort order.<br/><br />
				
				To add fully dynamic table column sorting, install this plugin: <a href="https://wordpress.org/plugins/table-sorter/" target="_blank">Table Sorter</a></div>
			
			<br class="eeClearFix" />
			
		</fieldset>
		
		
		<fieldset>
			
			<h2>The Uploader</h2>
			<span>File Uploader</span>
			<label for="eeUploadYes" class="eeRadioLabel">Single</label><input type="radio" name="eeAllowUploads" value="Yes" id="eeUploadYes" <?php if($eeAllowUploads == 'Yes') { echo 'checked'; } ?> />
			<label for="eeUploadMultu" class="eeRadioLabel">Multi</label><input type="radio" name="eeAllowUploads" value="Multi" id="eeUploadMulti" <?php if($eeAllowUploads == 'Multi') { echo 'checked'; } ?> />
			<label for="eeUploadNo" class="eeRadioLabel">None</label><input type="radio" name="eeAllowUploads" value="No" id="eeUploadNo" <?php if($eeAllowUploads == 'No') { echo 'checked'; } ?> />
					<div class="eeNote">The Multi-File Uploader may not work well with older web browsers.<br />
						<strong><?php echo $eeUploadLink; ?></strong>
					</div>
			<br class="eeClearFix" />
			
			<span>Get Uploader Info?</span><label for="eeFormYes" class="eeRadioLabel">Yes</label><input type="radio" name="eeShowForm" value="Yes" id="eeFormYes" <?php if($eeShowForm == 'Yes') { echo 'checked'; } ?> />
				<label for="eeFormNo" class="eeRadioLabel">No</label><input type="radio" name="eeShowForm" value="" id="eeFormNo" <?php if($eeShowForm != 'Yes') { echo 'checked'; } ?> />
				<br class="eeClearFix" />
				<div class="eeNote">Displays a form which must be filled out; Name, Email, with optional text Notes.<br />
					Submissions are sent to the Notice Email, set below.</div>
			<br class="eeClearFix" />
			
			
			<label for="eeUploadDir">Upload Directory:</label><input type="text" name="eeUploadDir" value="<?php if($eeUploadDir) { echo $eeUploadDir; } else { echo 'wp-content/uploads/simple-file-list'; } ?>" class="eeAdminInput" id="eeUploadDir" size="64" />
				<div class="eeNote">Relative to your website's public home folder. <em>wp-content/uploads/simple-file-list</em> is the default.<br />
					This will create the directory if it does not yet exist.<br />
					Your website must run under a FQDN in order to change this path.
				</div>
			
			<br class="eeClearFix" />
			
			<label for="ee_upload_max_filesize">How Big? (MB):</label><input type="number" min="1" max="<?php echo $ee_post_max_size; ?>" step="1" name="ee_upload_max_filesize" value="<?php echo $ee_upload_max_filesize; ?>" class="eeAdminInput" id="ee_upload_max_filesize" />
				<div class="eeNote">Your hosting limits the maximum file upload size to <strong><?php echo $ee_post_max_size; ?> MB</strong>.</div>
			
			<br class="eeClearFix" />
			
			<label for="eeFormats">Allowed Types:</label><textarea name="eeFormats" class="eeAdminInput" id="eeFormats" cols="64" rows="3" /><?php echo $eeFormats; ?></textarea>
				<div class="eeNote">Only use the file types you absolutely need, ie; jpg, jpeg, png, pdf, mp4, etc</div>
				
				
			<span>Add Owner?</span><label for="eeFileOwnerYes" class="eeRadioLabel">Yes</label><input type="radio" name="eeFileOwner" value="Yes" id="eeFileOwnerYes" <?php if($eeFileOwner == 'Yes') { echo 'checked'; } ?> />
				<label for="eeFileOwnerNo" class="eeRadioLabel">No</label><input type="radio" name="eeFileOwner" value="" id="eeFileOwner" <?php if($eeFileOwner != 'Yes') { echo 'checked'; } ?> />
					<div class="eeNote">Appends the logged-in Wordpress username to the file name. Only works for the single file uploader.</div>
			<br class="eeClearFix" />
			
		</fieldset>
		
		<fieldset>	
				
			<h2>Notifications</h2>	
				
			<label for="eeAdminTo">Notice Email:</label><input type="text" name="eeAdminTo" value="<?php echo $eeAdminTo; ?>" class="eeAdminInput" id="eeAdminTo" size="64" />
				<div class="eeNote">You'll get an email whenever a file is uploaded. Separate multiple addresses with a comma.</div>
			
		</fieldset>
		
		<fieldset>
		
			<input type="submit" name="submit" id="submit" value="SAVE" />
			
			<p><br /><?php echo $eeDisclaimer; ?><br />
			<a href="mailto:mitch@elementengage.com">Bug Reports / Feedback</a></p>
			
			<p><a class="eeBacklink" href="<?php echo $eeBackLink; ?>" target="_blank"><?php echo $eeBackLinkTitle; ?></a></p>
		
		</fieldset>
		
	</form>
	
</div>
	
<?php
	
$eeOutput = '';
	
include('ee-donations.php');
	
echo $eeOutput;	
	
?>