(function ($) {
$(document).ready(function() {
  $(document).on('click', '#mapping-select-button', function(e) {
    e.preventDefault(); // Stop normal form submission

    var form = $(this).closest('form');
    var formData = form.serialize(); // Collect all form inputs

    $.ajax({
      url: form.attr('action'),
      method: 'POST',
      data: formData,
      success: function(response, status, xhr) {
      if (xhr.status === 200 && response.trim().length > 0) {
        var newTable = $('<div>').html(response).find('table'); // parse HTML
        var currentTable = $('table');

        // Replace only the inside of the table
        currentTable.html(newTable.html());

        $(document).trigger('mapping:updated');
      } else {
        // alert('Received an empty or invalid response from the server.');
      }
    }
  });
});
});
})(jQuery)
