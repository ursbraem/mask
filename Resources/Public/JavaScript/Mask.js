define([
  'TYPO3/CMS/Mask/Contrib/vue',
  'TYPO3/CMS/Mask/Contrib/vuedraggable',
  'TYPO3/CMS/Mask/Components/NestedDraggable',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Backend/Icons',
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
        element: {
          key: '',
          label: '',
          shortLabel: '',
          description: '',
          icon: '',
          color: '#000000'
        },
        fieldTypes: [],
        fields: [],
        icons: {},
        editMode: false,
        availableTca: {},
        global: {
          activeField: {},
          clonedField: {}
        }
      }
    },
    mounted: function () {
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_fieldtypes).get()
        .then(
          async function (response) {
            mask.fieldTypes = await response.resolve();
            mask.fieldTypes.forEach(function (item) {
              new AjaxRequest(TYPO3.settings.ajaxUrls.mask_tca).withQueryArguments({table: 'tt_content', type: item.name}).get()
                .then(
                  async function (response) {
                    mask.availableTca[item.name] = await response.resolve();
                  }
                )
            });
          }
        );

      Icons.getIcon('actions-edit-delete', Icons.sizes.small).done(function (icon) {
        mask.icons.delete = icon;
      });
      Icons.getIcon('actions-move-move', Icons.sizes.small).done(function (icon) {
        mask.icons.move = icon;
      });
      require(['TYPO3/CMS/Mask/FontIconPicker']);
    },
    methods: {
      handleClone(item) {
        // Create a fresh copy of item
        let cloneMe = JSON.parse(JSON.stringify(item));
        this.$delete(cloneMe, 'uid');
        this.global.clonedField = cloneMe;
        return cloneMe;
      },
    },
    computed: {
      isExistingField: function () {
        if (!this.global.activeField.name) {
          return false;
        }
        var isExisting = false;
        this.availableTca[this.global.activeField.name].core.forEach(function (item) {
          if (item.field === mask.global.activeField.key) {
            isExisting = true;
          }
        });
        this.availableTca[this.global.activeField.name].mask.forEach(function (item) {
          if (item.field === mask.global.activeField.key) {
            isExisting = true;
          }
        });
        return isExisting;
      }
    }
  });
});
