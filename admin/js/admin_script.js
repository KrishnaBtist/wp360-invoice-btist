function wp360toggleCustomFun(elm){
    jQuery(elm).toggle()
}

jQuery(document).ready(function($) {
  var currentIndex = 0;

  function wp360_invoice_addNewItem() {
    currentIndex++; // Increment the item index
    var newItem = $('.wp360_invoiceItem:first').clone(); // Clone the first invoiceItem
    newItem.find('input').val('');
    newItem.find('input').attr('name', function(index, attr) {
      return attr.replace(/\[0\]/g, '[' + currentIndex + ']');
    });
    newItem.insertBefore('.wp360_invoice_addInvoiceItemCon');
    $('.wp360_invoice_removeInvoiceItem').toggle(currentIndex > 0);
  }

  function wp360_invoice_removeLastItem() {
    if (currentIndex > 0) {
      $('.wp360_invoiceItem:last').remove();
      currentIndex--;
      $('.wp360_invoice_removeInvoiceItem').toggle(currentIndex > 0);
    }
  }

  $('.wp360_invoice_addItem').on('click', function() {
      wp360_invoice_addNewItem();
  });

  $('.wp360_invoice_removeInvoiceItem').on('click', function() {
      wp360_invoice_removeLastItem();
  });

  $(document).on('change keydown keyup', '.wp360_invoice_itemsCon input', function(){
    let qty = 0;
    let unitPrice = 0;
    let itemPrice = 0;
    let totalPrice = 0;
    $('.wp360_invoice_itemsCon .wp360_invoiceItem').each(function(index){
        qty = $(this).find('.qtyField').val()
        unitPrice = $(this).find('.unitPriceField').val()
        itemPrice = qty * unitPrice
        totalPrice = totalPrice + itemPrice;
        qty = 0;
        unitPrice = 0;
        itemPrice = 0;
    })
    $('#totalAmountField').val(totalPrice)
  })
}); 

jQuery(document).on('click','.wp360-invoice-update-click',function(e){
    e.preventDefault();
    $this =  jQuery(this);
    var data = {
        'action': 'update_wp360_invoice' // Action to handle in PHP
    };
    jQuery.ajax({
        url: wp360_admin_data.ajax_url, // WordPress AJAX URL
        type: 'POST',
        data: data,
        beforeSend: function() {
          jQuery('.update-message').append('<span class="updating-message">Updating...</span>');
        },
        success: function(response) {
            let responseData = JSON.parse(response);

            console.log(JSON.stringify(responseData))
            var trElement    = jQuery('tr[data-slug="wp360-invoice"]');
            var divElement   = trElement.find('.plugin-version-author-uri');
            divElement.html('Version ' + responseData.aviliableVersion + ' | By <a href="https://wp360.in/">wp360</a>');

            jQuery('.updating-message').remove();
            jQuery('.plugin-update-tr').remove();
            //CUSTOM COUNT
            var pluginCountElement = jQuery('.plugin-count');
            console.log(JSON.stringify(pluginCountElement));
            if (pluginCountElement.length) {
                var currentCount = parseInt(pluginCountElement.html());
                var newCount = currentCount - 1;
                pluginCountElement.text(newCount);
            }
        },
        error: function(xhr, status, error) {
            console.error(error);
            jQuery('.updating-message').remove();
        }
    });


});