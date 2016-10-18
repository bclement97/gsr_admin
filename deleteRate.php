<?php
/**
 * Deletes the future scheduled rate. This page is accessible only by users whose Boalt ID is in
 * the $authUsers array and redirects back to rates.php.
 *
 * @author Brandon Clement
 * @since 29 October 2015
 */

include "/var/www/html/php-programs/globalLib.php";
  
$conn = oracleConnect("");
useStandardDateFormat($conn);

list($boaltID, $display_name, $category) = getUserByToken2($conn, $_SERVER['REQUEST_URI']);

$authUsers = array(
  5746, // Devin
  21431, // Brandon (Student Developer)
  21439, // Trish Millazo
  5656,  // Sheri Showalter
);

if (ACLAuthOK($boaltID, $authUsers) && array_key_exists("date", $_GET)) {
  $date = urldecode($_GET["date"]);
  
  $q = "DELETE FROM
        gsr_rates
      WHERE
        EFFECTIVE_DATE = TO_DATE(:effective_date, 'YYYY-MM-DD HH24:MI')";

  $stid = oci_parse($conn, $q);
  oci_bind_by_name($stid, ":effective_date", $date);
  oci_execute($stid);
}

header("Location: rates.php");
die();
?>
