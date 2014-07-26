<?php
/*
 * Missing Images Checker for ZenCart
 * Version 1.0.2
 * By Paul Williams (retched) 
 * @copyright Portions Copyright 2004-2014 Zen Cart Team
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

require('includes/application_top.php');

$only_errors='0';
// This can be set to either 1 for yes or 0 for no. By default, the script 
// will run through and report only errors. If you want to know about ALL
// images, change this to zero.
// Initializing row counter for output
$i = 0;

// Since we established connection, let's go ahead and pull up the records
// for all the products in the database. We only need the product name,
// product id, and product image fields. The product image and product id are 
// stored in TABLE_PRODUCTS. The product names are stored in the 
// TABLE_PRODUCTS_DESCRIPTION table. So a LEFT JOIN will be used.

$products_query="SELECT p.products_id, p.products_image, pd.products_name
                 FROM " . TABLE_PRODUCTS . " p
                 LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON p.products_id = pd.products_id
                 WHERE pd.language_id = " . (int)$_SESSION['languages_id'];

$products_result=$db->Execute($products_query);

while (!$products_result->EOF) {
  $products_info[]=array("id" => $products_result->fields['products_id'], "image" => $products_result->fields['products_image'], "name" => $products_result->fields['products_name']);
  $products_result->MoveNext();
}

/*
 * Okay so we got our database table results, what we need to do now is iterate 
 * over each row retrieved and do two things:
 *      1. Determine if the file exists. (file_exists($file_path))
 *      2. Determine if the image retrieved is infact an image. 
 *         (getimagesize($path))
 *      3. Determine if the image is stored in the correct format. (An image with 
 *         a .PNG should register as a PNG.)
 */
foreach ($products_info as &$product) { // We're going to directly modify this array and add a "result" column.
  // Test 1 (Does the file exists?)
  $file = DIR_FS_CATALOG_IMAGES . $product['image'];
  if (file_exists($file) && ((substr($file, -1) != "/") || empty($product['image']) )) {
    // The image exists... or the file found is ''. With Zen-Cart since a 
    // value of '' still generates a "NO IMAGE FOUND" link, we can call it 
    // a success.
    $product['error']="OK";
    $product['error_code']=0;
  } else {
    $product['error']=ERROR_FILE_NOT_FOUND;
    $product['error_code']=1;
  }

  if ($product['error'] == "OK" && $product['image'] !== '') {
    // Test 1 - PASS! (We found a file with something in the 'image' field.)
    // Test 2 (Is the file infact an image?)
    $image_check=getimagesize($file); // getimagesize returns 0 => height, 1 => width, and 2 => type.
    $image_check=$image_check[2];

    if (!in_array($image_check, array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_BMP))) {
      // The image found is not a valid web browser image. (Yes, SOME 
      // browsers can load TIFF's via a plugin, but most will not 
      // bother to use the plugin to open the file.) So it will
      // return an ERROR but can be ignored if you think that 
      // your customers will download the plugin.

      $product['error']=ERROR_NOT_WEB_FORMAT;
      $product['error_code']=2;
    }

    // Establishing image type array for return values of getimagesize()
    $image_type=array(0 => "UNKNOWN",
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

    $file_ext=strtolower(substr(strrchr($file, '.'), 1));  // Retrieve the file extension, convert it to lower case.

    unset($product['error']); // Clearing the error flag. 
    // We found a PNG, but the actual image is NOT a PNG.
    if ($file_ext == "png" && $image_check != IMAGETYPE_PNG) {
      $product['error']=sprintf(ERROR_IMAGE_FORMAT, "PNG", $image_type[$image_check]);
      $product['error_code']=3;
    }

    // We found a GIF, but the actual image is NOT a GIF.
    if ($file_ext == "gif" && $image_check != IMAGETYPE_GIF) {
      $product['error']=sprintf(ERROR_IMAGE_FORMAT, "GIF", $image_type[$image_check]);
      $product['error_code']=3;
    }

    // We found a JPEG but the actual image is NOT a JPEG.
    if ((in_array($file_ext, array("jpg", "jpeg", "jpe", "jfif", "jif")) && $image_check != IMAGETYPE_JPEG)) {
      $product['error']=sprintf(ERROR_IMAGE_FORMAT, "JPEG", $image_type[$image_check]);
      $product['error_code']=3;
    }

    // We found a BMP, but the actual image is NOT a BMP
    if ($file_ext == "bmp" && $image_check != IMAGETYPE_BMP) {
      $product['error']=sprintf(ERROR_IMAGE_FORMAT, "BMP", $image_type[$image_check]);
      $product['error_code']=3;
    }

    // If we made it this far without leaving this loop, the image passes all
    // three test! Before moving on to the next, give this file an OK error.
    if (!isset($product['error'])) {
      $product['error']="OK";
      $product['error_code']=0;
    }
  }
  if (!ini_get('safe_mode'))
    set_time_limit(30); // If you're not running PHP in Safe Mode, reset the timer to 30 seconds and start again. This is to allow for larger databases to be run. Will work on a better fix to this in a later update. Will likely involve a bit of refreshing and sending to the $_POST.
}
?>
<!doctype html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html <?php echo HTML_PARAMS; ?>>
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo CHARSET; ?>">
    <title>ZenCart Missing Image Check - by Paul Williams</title>
    <link rel="stylesheet" type="text/css" href="includes/stylesheet.css">
    <link rel="stylesheet" type="text/css" href="includes/cssjsmenuhover.css" media="all" id="hoverJS">
    <link rel="stylesheet" type="text/css" href="includes/missing_images.css">
    <script language="javascript" src="includes/menu.js"></script>
    <script language="javascript" src="includes/general.js"></script>
    <script type="text/javascript">
      <!--
      function init()
      {
        cssjsmenu('navbar');
        if (document.getElementById)
        {
          var kill = document.getElementById('hoverJS');
          kill.disabled = true;
        }
      }
      // -->
    </script>
  </head>
  <body onLoad="init()">
    <!-- header //-->
    <?php require(DIR_WS_INCLUDES . 'header.php'); ?>
    <!-- header_eof //-->
    <!-- body //-->
    <p class="centeredText"><span class="documentHeadline">Missing Image Checker v1.0 for ZenCart</span></p>
    <?php if (sizeof($products_info) > 0) { ?>
      <table class="resultsTable" >
        <tr class="headingRow">
          <th class="productIdcolumn">ID: </th>
          <th class="productNamecolumn">Product Name</th>
          <th class="productFilecolumn">Image File Name</th>
          <th class="productResultcolumn">Result</th>
        </tr>
        <?php
        foreach ($products_info as $product) {
          switch ($product['error_code']) {
            case (0):
              $errorclass="OK";
              break;
            case (1):
              $errorclass="redAlert";
              break;
            case (2):
              $errorclass="caution";
              break;
            case (3):
              $errorclass="mismatch";
              break;
          }
          ?>
          <?php if ($product['error'] == "OK" && $only_errors == 1) continue; // If we're only showing errors, skip any OK's ?>
          <tr class="<?php echo ($i % 2 === 0 ? "rowEven" : "rowOdd"); ?>">
            <td style="text-align: center;"><?php echo $product['id']; ?> </td>
            <td><?php echo $product['name']; ?> </td>
            <td><?php echo DIR_FS_CATALOG . DIR_WS_IMAGES . $product['image']; ?> </td>
            <td class="<?php echo $errorclass; ?>"><?php echo $product['error']; ?></td>
          </tr>
          <?php
          $i++;
        }
        ?>

        <?php if ($i > 0) { ?>
          <tr><td>&nbsp;</td><td colspan="3" style="text-align: center;">Grand Total Number of Missing Images: <?php echo $i; ?></td></tr>
        <?php } else { ?>
          <tr><td>&nbsp;</td><td colspan="3" style="text-align: center;">There are no errors to report in this view. <?php if ($only_errors == 1) { ?><br />This is normally a good thing as this means your images and product images check out. If you want a more verbose output, change $only_errors to equal 0 and rerun this script. <?php } ?></td></tr>
        <?php } ?>
      </table>

    <?php } else { ?>
      <p style="text-align: center;" class="OK">There are no products found in your database. If this is correct, there are no images to check. Run this script again after importing some products.</p>
    <?php } ?>
    <!-- body_eof //-->
    <!-- footer //-->
    <?php require(DIR_WS_INCLUDES . 'footer.php'); ?>
    <!-- footer_eof //-->
    <br>
  </body>
</html>
<?php require(DIR_WS_INCLUDES . 'application_bottom.php'); ?>
