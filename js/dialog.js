(function ($) {
  Drupal.behaviors.civiremote_dialog = {
    attach: function (context, settings) {
      $('.dialog-wrapper').each(function () {
        $('<button>')
            .addClass('dialog-toggle js-show btn btn-info')
            .attr('form', '')
            .text(Drupal.t('Show'))
            .prependTo($(this))
            .on('click', function () {
              let dialogId = $(this).closest('.dialog-wrapper')
                  .data('dialog-id');
              Drupal.dialog(
                  $('#' + dialogId),
                  {
                    buttons: [{
                      text: 'Close',
                      click: function () {
                        $(this).dialog('close');
                      }
                    }]
                  }).show();
              return false;
            })
      });
    }
  };
})(jQuery);
