<?php
/**
 * Displays the current rate (and future rate if scheduled) and allows a new rate to added. This page is accessible only
 * by users whose Boalt ID is in the $authUsers array.
 *
 * @author Brandon Clement
 * @since 29 October 2015
 */

include "/var/www/html/php-programs/globalLib.php";

function date_format_display($datetime) {
  return date_format($datetime, 'n/d/Y');
}

function date_format_server($datetime) {
  return date_format($datetime, 'Y-m-d');
}

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

$q = "SELECT
        *
      FROM
        law_projects.gsr_rates
      ORDER BY
        EFFECTIVE_DATE DESC";
$stid = oci_parse($conn, $q);
oci_execute($stid);

// The lastest effective date
$firstRow = null;

// Sets $row to the row with the most recent effective date on or before today
while (($row = oci_fetch_assoc($stid)) && ($row !== false)) {
  $date = new DateTime($row["EFFECTIVE_DATE"]);
  $row["Effective_DateTime"] = $date;

  if (is_null($firstRow)) {
    $firstRow = $row;
  }

  if ($date <= new DateTime()) {
    break;
  }
}

// Is there a scheduled (future) rate?
$scheduled = ($row !== $firstRow);
?>

<link type="text/css" rel="stylesheet" href="src/style.css" />
<link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/themes/smoothness/jquery-ui.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.3/jquery.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.3/jquery-ui.min.js"></script>
<script src="src/adminJavascript.js"></script>

<p id="current">
  <span class="u">Current Rate:</span>
  <br /><b>Max Hours:</b> <?= $row["MAX_HOURS"]; ?>
  <br /><b>Max Pay:</b> $<?= $row["MAX_PAY"]; ?>
  <br /><b>Effective:</b> <?= date_format_display($row["Effective_DateTime"]); ?>
</p>
<p id="scheduled">
  <span class="u">Scheduled Rate:</span>
  <?php
  if ($scheduled) {
    echo "(<a id=\"deleteRate\" href=\"deleteRate.php?date=" . urlencode($firstRow["EFFECTIVE_DATE"]) . "\">Cancel this rate</a>)";
    echo "<br /><b>Max Hours:</b> " . $firstRow["MAX_HOURS"];
    echo "<br /><b>Max Pay:</b> \$" . $firstRow["MAX_PAY"];
    echo "<br /><b>Effective:</b> " . date_format_display($firstRow["Effective_DateTime"]);
  } else {
    echo "<br /><b>No rate scheduled in the future.</b>";
  }
  ?>
</p>

<h2>Create New Rate</h2>

<form id="updateRate" method="post" action="updateRate.php">
  <table>
    <tr>
      <th>Max Hours</th>
      <th>Max Pay</th>
      <th>Effective Date</th>
    </tr>
    <tr>
      <td>
        <input type="number" name="max_hours" min="0" step="1" value="0" />
      </td>
      <td>
        <input type="number" name="max_pay" min="0" step="0.01" value="0" />
      </td>
      <td>
        <input type="date" name="effective_date" min="<?= date_format_server($row["Effective_DateTime"]); ?>" value="<?= date_format_server(new DateTime('tomorrow')); ?>" />
      </td>
    </tr>
    <tr>
      <td colspan="3">
        <input type="hidden" name="is_scheduled" value="<?= (int)$scheduled; ?>" />
        <input type="hidden" name="scheduled_date" value="<?= date_format_server($firstRow["EFFECTIVE_DATE"]); ?>" />
        <input type="submit" value="Create" />
        <input type="reset" value="Reset" />
      </td>
    </tr>
  </table>
</form>

<?php
//print $smartFooter;
?>
