$(function() {
  $("input[type='date']").datepicker();
  
  $("#deleteRate").click(function() {
    return confirm("Are you sure you want to cancel this scheduled rate?");
  });
  
  $("#updateRate").submit(function() {
    var inputDate = new Date($("input[name='effective_date']").val());
    var today = new Date();
    
    if (inputDate <= today) {
      alert("You must input a date after today (" + today.toDateString() + ")");
      return false;
    }
    
    if ($("input[name='is_scheduled']").val() == 1) {
      return confirm("Creating a new rate will replace the currently scheduled date. Would you like to proceed?");
    }
  });
});
