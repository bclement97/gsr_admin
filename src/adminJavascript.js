$(function() {
  $("input[type='date']").datepicker({
    dateFormat: "yy-mm-dd"
  });

  $("#deleteRate").click(function() {
    return confirm("Are you sure you want to cancel this scheduled rate?");
  });

  $("#updateRate").submit(function() {
    var inputDate = new Date($("input[name='effective_date']").val());
    // .replace() hacks the date so that it isn't a day off
    // See http://stackoverflow.com/questions/7556591/javascript-date-object-always-one-day-off#answer-31732581
    var curr_effective_date = new Date($("input[name='curr_effective_date']").val().replace(/-/g, '\/'));

    if (inputDate <= curr_effective_date) {
      alert("You must input a date after the current effective date (" + curr_effective_date.toDateString() + ")");
      return false;
    }

    if ($("input[name='is_scheduled']").val() == 1) {
      return confirm("Creating a new rate will replace the currently scheduled date. Would you like to proceed?");
    }
  });
});
