/******/ (() => { // webpackBootstrap
/*!**********************!*\
  !*** ./src/admin.js ***!
  \**********************/
(function () {
  'use strict';

  $(document).ready(function () {
    const adminUrl = OC.generateUrl('/apps/pdftool/settings/admin');
    const $engineInputs = $('#pdftool_engine_setting input[type="radio"]');
    const $maxPagesInput = $('#pdftool_max_pages');
    const $maxPdfsInput = $('#pdftool_max_pdfs');
    const saveSettings = async function () {
      $.ajax({
        url: adminUrl,
        method: 'POST',
        contentType: 'application/json',
        headers: {
          requesttoken: OC.requestToken
        },
        data: JSON.stringify({
          engine: $engineInputs.filter(':checked').val(),
          maxPages: Number($maxPagesInput.val()),
          maxPdfs: Number($maxPdfsInput.val())
        }),
        success: function () {
          OC.Notification.showTemporary(OC.L10N.translate('pdftool', 'PDF Tool settings saved.'));
        },
        error: function (response) {
          const message = response.responseJSON?.message || OC.L10N.translate('pdftool', 'Could not save PDF Tool settings.');
          OC.Notification.showTemporary(message);
        }
      });
    };
    $engineInputs.change(saveSettings);
    $maxPagesInput.change(saveSettings);
    $maxPdfsInput.change(saveSettings);
  });
})();
/******/ })()
;
//# sourceMappingURL=pdftool-admin.js.map?v=6df07bd374a02201373a