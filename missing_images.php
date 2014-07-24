<?php 
/**
 * Missing Images Checker for ZenCart
 * Version 1.0.2
 * By Paul Williams (retched) 
 * @copyright Portions Copyright 2004-2006 Zen Cart Team
****************************************************************************
    Copyright (C) 2014  Paul Williams/IWL Entertainment

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
****************************************************************************/
// This file will retrieve the entirety of your ZenCart product database and 
// search your ZenCart installation to see if the file exists, is an image
// and matches the image type it claims to be.
// 
// If there are any problems, the script will generate an error for you 
// and describe possible remedies. 
//
// To get the most of this file, you should disable PHP's safe mode as this file
// may take an excessively long time if you're running this script on a LARGE 
// database. Large being anywhere near 2000 or more products. In addition,
// you should also probably run this on a dedicated server or a local testing
// server as opposed to online.
//
// Also note, there are **NO CHANGES BEING MADE TO YOUR DATABASE**. This 
// script will not make nor commit any changes to your database.
//
// To get started, there are three variables you need to change, although you 
// will mostly only need to change one.
//
// Need assistance? Feel free to contact me via email: retched@iwle.com
// Be sure to label the subject as "Zen-Cart Missing Images Checker" so
// I know what message is coming at me.
///////////////////////////////////////////////////
// C O N F I G U R A T I O N
//
// V A R I A B L E S
///////////////////////////////////////////////////

$path_to_zen_cart_configure_php = './includes/'; 
    // Enter the full path information to the /catalog/includes/configure.php
    // of your ZenCart installation. 
    //
    // DO use relative paths (if you wish) based on the location of this file.
    // (ex: ../zencart/includes/ )
    // DO include a trailing slash.
    // DO include any drive names (Windows) (ex. c:/wamp/zencart/includes/ )
    // 
    // **DO NOT USE BACKSLASHES**, use forward slashes or else this will fail.
    // **DO NOT** include configure.php or ANY filename at the end of this
    // variable. This is being done by the script.

	// I removed the prompt to see if you want to use MySQL or MySQLi as you 
	// REALLY should be using MySQLi connections instead of MySQL. The script 
	// will now check to see if it can run mysql or mysqli. MySQL functions 
	// will be obsolete soon as they are deprecated in php 5.5.0 and will be 
	// removed soon. This option will likely be removed in a future update 
	// when MySQL is no longer available by default with PHP. If for some 
	// reason you REALLY need to use MySQL instead of MySQLi even though 
	// MySQLi is available, you can uncomment the line below (delete the // in
	// front and save) to force the script to run MySQL instead of MySQLi.
	//
	// $force_mysql = true;

$zen_language_id = '1';
    // This can be left at 1. This is only really used for displaying the 
    // desired product name in a given language. For example, I only have 
    // one language is use on my table. So I would leave this at 1. But 
    // say you programmed 1 for English and 2 for Spanish and you want 
    // to see the product names in Spanish. You would change this to 2.

$only_errors = '1';
    // This can be set to either 1 for yes or 0 for no. By default, the script 
    // will run through and report only errors. If you want to know about ALL
    // images, change this to zero.

/********************************************************************************
**                                                                             **
**                DO NOT EDIT BELOW THIS LINE UNLESS YOU REALLY,               **
**                       REALLY KNOW WHAT YOU ARE DOING                        **
**                                                                             **
********************************************************************************/

// Define language definitions to avoid copy/pasting almost the same exact sentence.
define('ERROR_IMAGE_FORMAT', 'The image is named as a %s file but instead registers as a %s! Try to resave and reupload your file in the proper format.');
define('ERROR_FILE_NOT_FOUND', "Image file does not exist. Edit the product's details and click \"delete image\" to remove from database.");
define('ERROR_NOT_WEB_FORMAT', "The file found is either not an image or not an image that can be inherently loaded in a web browser. Try reuploading the file in PNG, GIF, JPG, or BMP format.");

// Initializing row counter for output
$i = 0;

// Load the configure file for zencart. (This is being used to load the database information and general path information.)
require_once($path_to_zen_cart_configure_php . "configure.php");

// Load the definition file for the database table names. 
require_once(DIR_FS_CATALOG . "includes/database_tables.php");  //-20140724-lat9-Use configure.php setting, enables using /includes/local/configure.php

// Our main variable where the database information will be stored.
$products_info = array();

//
// Establish Database Connection and retrieve information
//

// The query to select the necessary fields. ONLY EDIT THIS IF YOU NEED TO RUN 
// SHORTER QUERIES!
$products_query = "SELECT p.products_id, p.products_image, pd.products_name FROM " . TABLE_PRODUCTS . " p LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id WHERE pd.language_id = " . (int)$zen_language_id;

if (function_exists('mysqli_connect') && ($force_mysql !== true) ) {

    // establish database connection
    $db = mysqli_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD, DB_DATABASE) or die ("Cannot connect to database. MySQL returned this error. <br />" . mysqli_error($db));
    
    // Since we established connection, let's go ahead and pull up the records
    // for all the products in the database. We only need the product name,
    // product id, and product image fields. The product image and product id are 
    // stored in TABLE_PRODUCTS. The product names are stored in the 
    // TABLE_PRODUCTS_DESCRIPTION table. So a LEFT JOIN will be used.

    if (!($products_result = mysqli_query($db, $products_query))) {
		die("Cannot execute query. MySQL returned this error.<br />" . mysqli_error($db));
	}
    
    while($row = mysqli_fetch_array($products_result)) {
        $products_info[] = array("id" => $row[0], "image" => $row[1], "name" => $row[2]);
    }

    mysqli_close($db);

} else if (function_exists('mysql_connect') || ($force_mysql === true) ) {
    // establish database connection
    $db = mysql_connect(DB_SERVER, DB_SERVER_USERNAME, DB_SERVER_PASSWORD) or die ("Cannot connect to database. MySQL returned this error. <br />" . mysql_error($db));
   
	// mysql requires to select a database first before we can use it.
    mysql_select_db(DB_DATABASE, $db);

    // Since we established connection, let's go ahead and pull up the records
    // for all the products in the database. We only need the product name,
    // product id, and product image fields. The product image and product id are 
    // stored in TABLE_PRODUCTS. The product names are stored in the 
    // TABLE_PRODUCTS_DESCRIPTION table. So a LEFT JOIN will be used.

    $products_result = mysql_query($products_query) or die("Cannot execute query. MySQL returned this error.<br />" . mysql_error($db));

    while($row = mysql_fetch_array($products_result)) {
        $products_info[] = array("id" => $row[0], "image" => $row[1], "name" => $row[2]);
    }

    mysql_close($db);
} else { // We're only using MySQL or MySQLi, no other databases can be used.

    die("<b>Invalid Database Detected!</b> Only MySQL and MySQLi databases are supported at this time.");

}

////////////////////////////////////////////////////////////////////////////////
// Okay so we got our database table results, what we need to do now is iterate 
// over each row retrieved and do two things:
//      1. Determine if the file exists. (file_exists($file_path))
//      2. Determine if the image retrieved is infact an image. 
//         (getimagesize($path))
//      3. Determine if the image is stored in the correct format. (An image with 
//         a .PNG should register as a PNG.)
/////////////////////////////////////////////////////////////////////////////////
foreach($products_info as &$product) { // We're going to directly modify this array and add a "result" column.

    // Test 1 (Does the file exists?)
    $file = DIR_FS_CATALOG . DIR_WS_IMAGES . $product['image'];
    if(file_exists($file) && ((substr($file, -1) != "/") || empty($product['image']) )) {
		// The image exists... or the file found is ''. With Zen-Cart since a 
		// value of '' still generates a "NO IMAGE FOUND" link, we can call it 
		// a success.
        $product['error'] = "OK";
		$product['error_code'] = 0;
    } else {
		$product['error'] = ERROR_FILE_NOT_FOUND;
		$product['error_code'] = 1;
	}
	
	if ($product['error'] == "OK" && $product['image'] !== '') {
        // Test 1 - PASS! (We found a file with something in the 'image' field.)
        // Test 2 (Is the file infact an image?)
        $image_check = getimagesize($file); // getimagesize returns 0 => height, 1 => width, and 2 => type.
		$image_check = $image_check[2];
		
        if (!in_array($image_check, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP))) { 
            // The image found is not a valid web browser image. (Yes, SOME 
            // browsers can load TIFF's via a plugin, but most will not 
            // bother to use the plugin to open the file.) So it will
            // return an ERROR but can be ignored if you think that 
            // your customers will download the plugin.

            $product['error'] = ERROR_NOT_WEB_FORMAT;
			$product['error_code'] = 2;
        }

		// Establishing image type array for return values of getimagesize()
		$image_type = array( 0 => "UNKNOWN",
						     1 => "GIF",
							 2 => "JPEG",
							 3 => "PNG",
							 4 => "SWF",
							 5 => "PSD",
							 6 => "BMP",
							 7 => "TIFF",
							 8 => "TIFF",
							 9 => "JPC",
							10 => "JP2",
							11 => "JPX",
							12 => "JB2",
							13 => "SWC",
							14 => "IFF",
							15 => "WBMP",
							16 => "XBM",
							17 => "ICO",
							18 => "COUNT");
							
        //////////////////////////////////////////////////////////////////////
        // TEST 1 - PASS! (We found the file.)
        // TEST 2 - PASS! (The file found is an image.)
        // TEST 3 (Does the file found match the image type? (An image with a
        // .PNG extension should register as a PNG.)
        /////////////////////////////////////////////////////////////////////

        $file_ext = strtolower(substr(strrchr($file,'.'),1));  // Retrieve the file extension, convert it to lower case.

        unset($product['error']); // Clearing the error flag. 

        // We found a PNG, but the actual image is NOT a PNG.
        if ($file_ext == "png" && $image_check != IMAGETYPE_PNG) {
            $product['error'] = sprintf(ERROR_IMAGE_FORMAT,"PNG",$image_type[$image_check]);
			$product['error_code'] = 3;
        }

        // We found a GIF, but the actual image is NOT a GIF.
        if ($file_ext == "gif" && $image_check != IMAGETYPE_GIF) {
            $product['error'] = sprintf(ERROR_IMAGE_FORMAT,"GIF",$image_type[$image_check]);
			$product['error_code'] = 3;
        }

        // We found a JPEG but the actual image is NOT a JPEG.
        if ((in_array($file_ext,  array("jpg", "jpeg", "jpe", "jfif", "jif")) && $image_check != IMAGETYPE_JPEG)) {
            $product['error'] = sprintf(ERROR_IMAGE_FORMAT,"JPEG",$image_type[$image_check]);
			$product['error_code'] = 3;
        }

        // We found a BMP, but the actual image is NOT a BMP
        if ($file_ext == "bmp" && $image_check != IMAGETYPE_BMP) {
            $product['error'] = sprintf(ERROR_IMAGE_FORMAT,"BMP",$image_type[$image_check]);
			$product['error_code'] = 3;
        }

        // If we made it this far without leaving this loop, the image passes all
        // three test! Before moving on to the next, give this file an OK error.
        if(!isset($product['error'])) {
			$product['error'] = "OK";
			$product['error_code'] = 0;
		}
    }
    if (!ini_get('safe_mode')) set_time_limit(30); // If you're not running PHP in Safe Mode, reset the timer to 30 seconds and start again. This is to allow for larger databases to be run. Will work on a better fix to this in a later update. Will likely involve a bit of refreshing and sending to the $_POST.
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>ZenCart Missing Image Check - by Paul Williams</title>
<style type="text/css">
@media screen {
.documentHeadline {
	text-align: center;
	font-size: 36px;
	font-weight: bold;
}
.centeredText {
	text-align: center;
}
body {
	background: #E1E1E1;
	font-family: arial, helvetica, sans-serif !important;
	font-size: 13px !important;
	color: #242424;
	line-height: 2.3em !important;
}

.resultsTable {
	width: 80%;
	margin: auto;
	border-collapse: collapse;
	line-height: 1.7em;
}
.rowEven {
	background: #FFFFFF;
}

.rowEven:hover {
	background: #DADADA;
}

.rowOdd {
	background: #EFEFEF;
}
.rowOdd:hover {
	background: #D2D2D2;
}
.headingRow {
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background: #000000;
}
.mismatch, .caution {
	background: yellow !important;
	font-style: italic;
}
.redAlert {
	color: #FF0000 !important;
	font-weight: bold;
}
.OK {
	padding: 0 0 0 50px;
	color: #009900 !important;
	font-weight: bold;
}

.productIDcolumn { width: 5%; }
.productNamecolumn { width: 20%; }
.productFilecolumn { width: 40%; }
.productResultcolumn { width:35%; }
}

@media print  {
.documentHeadline {
	text-align: center;
	font-size: 36px;
	font-weight: bold;
}
.centeredText {
	text-align: center;
}
body {
	font-family: arial, helvetica, sans-serif !important;
	font-size: 13px !important;
	color: #000000;
	line-height: 2.3em !important;
}

.resultsTable {
	width: 600px;
	margin: auto;
	border-collapse: collapse;
	line-height: 1.7em;
	table-layout:fixed;
}
.resultsTable td {
	word-wrap: break-word;
}
.rowEven {
	background: #FFFFFF;
}

.rowEven:hover {
	background: #DADADA;
}

.rowOdd {
	background: #EFEFEF;
}
.rowOdd:hover {
	background: #D2D2D2;
}
.headingRow {
	text-align: center;
	font-weight: bold;
	color: #FFFFFF;
	background: #000000;
}
.mismatch, .caution {
	background: yellow !important;
	font-style: italic;
}
.redAlert {
	color: #FF0000 !important;
	font-weight: bold;
}
.OK {
	padding: 0 0 0 50px;
	color: #009900 !important;
	font-weight: bold;
}

.productIDcolumn { width: 5%; }
.productNamecolumn { width: 20%; }
.productFilecolumn { width: 40%; }
.productResultcolumn { width:35%; }
}
</style>
</head>
<body>
<p class="centeredText"><span class="documentHeadline">Missing Image Checker for ZenCart v1.0.2</span></p>
<?php if (sizeof($products_info) > 0) { ?>
<table class="resultsTable" >
  <tr class="headingRow">
    <th class="productIdcolumn">ID: </th>
    <th class="productNamecolumn">Product Name</th>
    <th class="productFilecolumn">Image File Name</th>
    <th class="productResultcolumn">Result</th>
  </tr>
  <?php foreach($products_info as $product) { ?>
  <?php 
  	switch($product['error_code']) {
		case (0):
		$errorclass = "OK";
		break;
		case (1):
		$errorclass = "redAlert";
		break;
		case (2): 
		$errorclass = "caution";
		break;
		case (3):
		$errorclass = "mismatch";
		break;
	}
?>
  <?php if($product['error'] == "OK" && $only_errors == 1) continue; // If we're only showing errors, skip any OK's ?>
  <tr class="<?php echo ($i % 2 === 0 ? "rowEven" : "rowOdd"); ?>">
  	<td style="text-align: center;"><?php echo $product['id']; ?> </td>
    <td><?php echo $product['name']; ?> </td>
    <td><?php echo DIR_FS_CATALOG . DIR_WS_IMAGES . $product['image']; ?> </td>
    <td class="<?php echo $errorclass; ?>"><?php echo $product['error']; ?></td>
  </tr>
  <?php $i++; } ?>
  
  <?php if ($i > 0) { ?>
  <tr><td>&nbsp;</td><td colspan="3" style="text-align: center;">Grand Total Number of Missing Images: <?php echo $i; ?></td></tr>
  <?php } else { ?>
  <tr><td>&nbsp;</td><td colspan="3" style="text-align: center;">There are no errors to report in this view. <?php if ($only_errors == 1) { ?><br />This is normally a good thing as this means your images and product images check out. If you want a more verbose output, change $only_errors to equal 0 and rerun this script. <?php } ?></td></tr>
  <?php } ?>
</table>

<?php } else { ?>
<p style="text-align: center;" class="OK">There are no products found in your database. If this is correct, there are no images to check. Run this script again after importing some products.</p>
<?php } ?>
</body>
</html>
