/**
 * Calucates GSR Salary info based on calculator input. The input is sent to ../processAjax.php to be added to the
 * database table. The calculated values are updated in the result table on the main calculator page.
 *
 * IMPORTANT: Although this file has a .php extension, it is a javascript file. PHP is used to retrieve the EFFECTIVE_DATE,
 * MAX_HOURS, and MAX_PAY values from the database so that this file does not need to be directly edited every time
 * there is a rate change.
 *
 * @author Brandon Clement
 * @since 29 October 2015
 */

<?php
include "/var/www/html/php-programs/globalLib.php";

$conn = oracleConnect("");
useStandardDateFormat($conn);

$q = "SELECT
        *
      FROM
        gsr_rates
      ORDER BY
        EFFECTIVE_DATE DESC";
$stid = oci_parse($conn, $q);
oci_execute($stid);

// Gets the row with the most recent effective date on or before today
while (($row = oci_fetch_assoc($stid)) && ($row !== false)) {
  $date = new DateTime($row['EFFECTIVE_DATE']);
  
  if ($date <= new DateTime()) {
    break;
  }
}
?>

var EFFECTIVE_DATE = "<?= date_format($date, 'n/j/Y'); ?>";
var MAX_HOURS = <?= $row['MAX_HOURS']; ?>;
var MAX_PAY = <?= $row['MAX_PAY']; ?>;

/**
 * Number.floorHundredth()
 *
 * @return Number Number rounded down to nearest hundredth
 */
Number.prototype.floorHundredth = function() {
  return Math.floor((this.valueOf() + 0.001) * 100) / 100;
};

/**
 * divideHundredths()
 * 
 * Properly divides two floats with up to hundredth values
 * 
 * @param Number The dividend
 * @param Number The divisor
 * @return Number The quotient
 */
var divideHundredths = function(dividend, divisor) {
  return (dividend.floorHundredth() * 100.0) / (divisor.floorHundredth() * 100.0);
};

/**
 * getHours()
 *
 * @param Number percent Whole number percent time
 * @return Number Number of hours to two decimal places
 */
var getHours = function(percent) {
  return (MAX_HOURS * percent / 100).toFixed(2);
};

/**
 * getPay()
 *
 * @param Number percent Whole number percent time
 * @return Number Salary to two decimal places
 */
var getPay = function(percent) {
  return (MAX_PAY * percent / 100).toFixed(2);
};

$(document).ready(function() {
  $("#effective").text(EFFECTIVE_DATE);
  
  $("#reset").click(function(e) {
    $("#percentCell").text(0);
    $("#hoursCell").text("0.00");
    $("#payCell").text("0.00");
    $("#remissionCell").text("No Fee Remission");
    $("#roundInfo").css("display", "none");
  });
  
  $("#calculator").submit(function(e) {
    e.preventDefault(); // Don't actually submit form since using AJAX
    
    var method = $("#method").val();
    var data = $("#data").val();
    var percent = 0;
    var newPercent = 0;
    var remissionText = "";
    
    // Validate data in case HTML form validation fails
    var dataRegex = new RegExp($("#data").attr("pattern"));
    
    if (dataRegex.test(data)) {
      data = dataRegex.exec(data);
      data = Number(data).floorHundredth(); // Truncates all data to two decimal places
    } else {
      alert("Please input " + $("#data").attr("title"));
      return;
    }
    
    // Data validation passes
    
    switch (method) {
      case "pay":
        percent = divideHundredths(data, MAX_PAY) * 100;
        break;
      case "hours":
        percent = divideHundredths(data, MAX_HOURS) * 100;
        break;
      case "percent":
        percent = data;
        break;
      default:
        console.error("Invalid value for #method");
    }
    
    newPercent = Math.floor(percent);
    
    if (newPercent > 100) {
      newPercent = 100;
    } else if (newPercent < 0) {
      newPercent = 0;
    }
    
    $("#percentCell").text(newPercent);
    $("#hoursCell").text(getHours(newPercent));
    $("#payCell").text(getPay(newPercent));
    
    if (newPercent < 25) {
      remissionText = "No Fee Remission";
    } else if (newPercent < 45) {
      remissionText = "Partial Fee Remission";
    } else if (newPercent < 50) {
      remissionText = "Full Fee Remission";
    } else {
      remissionText = "Summer Only";
    }
    
    $("#remissionCell").text(remissionText);
    
    if (newPercent != percent) {
      $("#roundInfo").css("display", "block");
    } else {
      $("#roundInfo").css("display", "none");
    }
    
    $.ajax("processAjax.php", {
      type: "POST",
      data: {
        method: method,
        data: data
      },
      error: function(xhr, status, error) {
        console.error(error);
      }
    });
  });
});
