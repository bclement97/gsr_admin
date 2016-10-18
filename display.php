<?php
/**
 * Gets and displays MAX_DISP_ROWS (default: 50) most recent GSR Salary Calulator computations. Results are paginated
 * using the "start" GET variable. This page is accessible only by users whose Boalt ID is in the $authUsers array.
 *
 * @author Brandon Clement
 * @since 29 October 2015
 */

include "/var/www/html/php-programs/globalLib.php";

//list($smartHeader, $smartFooter) = getSetHeaderFooter("/human-resources/hire-a-student/gsrs/gsr-salary-calculator-admin/", "", 1);

//print $smartHeader;

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
  print $smartFooter;
  die();
}

// Max number of rows to display
define("MAX_DISP_ROWS", 50);

$startRow = (array_key_exists("start", $_GET)) ? $_GET["start"] : 0;

$stid = oci_parse($conn, "SELECT COUNT(*) total FROM gsr_salary");
oci_execute($stid);
$row = oci_fetch_assoc($stid);
$totalRows = $row["TOTAL"];

$q = "SELECT
        *
      FROM
        law_projects.gsr_salary
      ORDER BY
        TIMESTAMP DESC
      OFFSET
        :start_row ROWS
      FETCH NEXT
        ".MAX_DISP_ROWS." ROWS ONLY";

$stid = oci_parse($conn, $q);
oci_bind_by_name($stid, ":start_row", $startRow);
oci_execute($stid);

$rows = array();
$numRows = oci_fetch_all($stid, $rows);
?>

<link type="text/css" rel="stylesheet" href="src/style.css" />

<form action="export.php" method="post" target="_blank">
  <button type="submit" name="all" value="1">Export all to CSV file</button>
  <button type="submit" name="view" value="<?= $startRow.'_'.MAX_DISP_ROWS ?>">Export view to CSV file</button>
</form>

<table>
  <thead>
    <tr>
      <?php
      foreach (array_keys($rows) as $col) {
        echo "<th>$col</th>";
      }
      ?>
    </tr>
  </thead>
  <tfoot>
    <tr>
      <td colspan="<?= count(array_keys($rows)) ?>">
        <?php
        $lastStart = $startRow - MAX_DISP_ROWS;
        $lastAttr = ($lastStart >= 0) ? 'href="?start='.$lastStart.'"' : 'style="visibility: hidden;"';
        $nextStart = $startRow + MAX_DISP_ROWS;
        $nextAttr = ($nextStart < $totalRows) ? 'href="?start='.$nextStart.'"' : 'style="visibility: hidden;"';
        ?>
        <span class="last"><a <?= $lastAttr ?>>&larr; Last <?= MAX_DISP_ROWS ?> Rows</a></span>
        <b>Rows <?= ($startRow + 1) ?> - <?= ($startRow + $numRows) ?></b>
        <span class="next"><a <?= $nextAttr ?>>Next <?= MAX_DISP_ROWS ?> Rows &rarr;</a></span>
      </td>
    </tr>
  </tfoot>
  <tbody>
    <?php
    for ($currRow = 0; $currRow < $numRows; $currRow++) {
      echo "<tr>";
      
      foreach ($rows as $values) {
        echo "<td>{$values[$currRow]}</td>";
      }
      
      echo "</tr>";
    }
    ?>
  </tbody>
</table>

<?php print $smartFooter; ?>
