define([
  'TYPO3/CMS/Mask/Contrib/vue',
  'TYPO3/CMS/Mask/Contrib/vuedraggable',
  'TYPO3/CMS/Mask/NestedDraggable',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Backend/Icons'
], function (Vue, draggable, nestedDraggable, AjaxRequest, Icons) {
  if (!document.getElementById('mask')) {
    return;
  }
  var mask = new Vue({
    el: '#mask',
    components: {
      draggable,
      nestedDraggable
    },
    data: function () {
      return {
        fieldTypes: [],
        fields: [],
        icons: {},
      }
    },
    mounted: function () {
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_fieldtypes).get()
        .then(
          async function (response) {
            mask.fieldTypes = await response.resolve();
          }
        );
      Icons.getIcon('actions-edit-delete', Icons.sizes.small).done(function (icon) {
        mask.icons.delete = icon;
      });
      Icons.getIcon('actions-move-move', Icons.sizes.small).done(function (icon) {
        mask.icons.move = icon;
      });
    },
    methods: {
      handleClone(item) {
        // Create a fresh copy of item
        let cloneMe = JSON.parse(JSON.stringify(item));
        this.$delete(cloneMe, 'uid');

        return cloneMe;
      },
    }
  });
});
