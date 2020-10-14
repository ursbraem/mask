define(['jquery', 'TYPO3/CMS/Core/Ajax/AjaxRequest', 'TYPO3/CMS/Mask/Contrib/FontIconPicker'], function ($, AjaxRequest) {
  new AjaxRequest(TYPO3.settings.ajaxUrls.mask_icons).get()
    .then(
      async function (response) {
        var icons = await response.resolve();
        $('#meta_icon').fontIconPicker({
          source: icons
        });
      }
    );
});
