(function ($) {
  Drupal.behaviors.civiremote_dialog = {
    attach: function (context, settings) {
      $('.dialog-wrapper', context).each(function () {
        $('<button>')
            .addClass('dialog-toggle js-show btn btn-info')
            .attr('form', '')
            .text($(this).data('dialog-label'))
            .prependTo($(this))
            .on('click', function () {
              let dialogId = $(this).closest('.dialog-wrapper')
                  .data('dialog-id');
              Drupal.dialog(
                  $('#' + dialogId),
                  {
                    width: '90%',
                    buttons: [{
                      text: 'Close',
                      click: function () {
                        $(this).dialog('close');
                      }
                    }]
                  }).showModal();
              return false;
            })
      });
    }
  };
})(jQuery);
