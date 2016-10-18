<?php
/**
 * Adds a new rate and replaces the future scheduled rate if there is one. This page is accessible only
 * by users whose Boalt ID is in the $authUsers array and redirects back to rates.php.
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

if (ACLAuthOK($boaltID, $authUsers) && array_key_exists("max_hours", $_POST) && array_key_exists("max_pay", $_POST)
     && array_key_exists("effective_date", $_POST) && array_key_exists("is_scheduled", $_POST) && array_key_exists("scheduled_date", $_POST)) {
  
  // Removes the scheduled (future) rate if there is one
  if ($_POST["is_scheduled"] == 1) {
    $q = "DELETE FROM
            law_projects.gsr_rates
          WHERE
            EFFECTIVE_DATE = TO_DATE(:scheduled_date, 'YYYY-MM-DD HH24:MI')";

    $stid = oci_parse($conn, $q);
    oci_bind_by_name($stid, ":scheduled_date", $_POST["scheduled_date"]);
    oci_execute($stid);
  }
  
  $q = "INSERT INTO law_projects.gsr_rates (
          MAX_HOURS,
          MAX_PAY,
          EFFECTIVE_DATE
        )
        VALUES (
          :hours,
          :pay,
          TO_DATE(:effective_date, 'MM-DD-YYYY')
        )";
    
  $stid = oci_parse($conn, $q);
  oci_bind_by_name($stid, ":hours", $_POST["max_hours"]);
  oci_bind_by_name($stid, ":pay", $_POST["max_pay"]);
  oci_bind_by_name($stid, ":effective_date", $_POST["effective_date"]);
  oci_execute($stid);
}

header("Location: rates.php");
die();
?>

?>
