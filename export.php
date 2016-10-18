<?php
/**
 * Puts either all or a range of GSR Salary Calulator computations in gsrSalaryExport.csv and downloads it to the client.
 * Ranges are in the format "{start row}_{number of rows}". This page is not directly viewable and is accessible only by
 * users whose Boalt ID is in the $authUsers array.
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

if (!ACLAuthOK($boaltID, $authUsers)) {
  echo "<h1>Unauthorized</h1>";
  die();
}

$q = "SELECT
        *
      FROM
        law_projects.gsr_salary
      ORDER BY
        TIMESTAMP DESC";

if (array_key_exists("view", $_POST)) {
  $view = explode("_", $_POST["view"]);
    
  $q .= "
      OFFSET
        :start_row ROWS
      FETCH NEXT
        :num_rows ROWS ONLY";
  
  $stid = oci_parse($conn, $q);
  oci_bind_by_name($stid, ":start_row", $view[0]);
  oci_bind_by_name($stid, ":num_rows", $view[1]);
} else if (array_key_exists("all", $_POST)) {
  $stid = oci_parse($conn, $q);
} else {
  echo "<h1>Malformed Request</h1>";
  die();
}

oci_execute($stid);

$rows = array();
$numRows = oci_fetch_all($stid, $rows);
$numCols = count(array_keys($rows));

$filename = "gsrSalaryExport.csv";
$csvFile = fopen($filename, "w");

fputcsv($csvFile, array_keys($rows));

for ($currRow = 0; $currRow < $numRows; $currRow++) {
  $row = array();

  foreach ($rows as $values) {
    $row[] = $values[$currRow];
  }
  
  fputcsv($csvFile, $row);
  unset($row);
}

fclose($csvFile);

// Download the file to the client
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=$filename");
readfile($filename);
?>
