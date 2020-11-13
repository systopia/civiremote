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
              Drupal.dialog(
                  $(this).closest('.dialog-wrapper')
                      .find('.dialog-content'),
                  {
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
