define([
  'TYPO3/CMS/Mask/Contrib/vue',
  'TYPO3/CMS/Mask/Contrib/vuedraggable',
  'TYPO3/CMS/Mask/Components/NestedDraggable',
  'TYPO3/CMS/Mask/Components/FormField',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Backend/Icons',
], function (Vue, draggable, nestedDraggable, formField, AjaxRequest, Icons) {
  if (!document.getElementById('mask')) {
    return;
  }

  var mask = new Vue({
    el: '#mask',
    components: {
      draggable,
      nestedDraggable,
      formField,
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
        tcaFields: {},
        tabs: {},
        fields: [],
        language: [],
        icons: {},
        editMode: false,
        availableTca: {},
        global: {
          activeField: {},
          clonedField: {},
          richtextConfiguration: {},
          currentTab: 'general',
          ctypes: {}
        }
      }
    },
    mounted: function () {
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_fieldtypes).get()
        .then(
          async function (response) {
            mask.fieldTypes = await response.resolve();
            mask.fieldTypes.forEach(function (item) {
              // TODO table tt_content or pages
              new AjaxRequest(TYPO3.settings.ajaxUrls.mask_existing_tca).withQueryArguments({table: 'tt_content', type: item.name}).get()
                .then(
                  async function (response) {
                    mask.availableTca[item.name] = await response.resolve();
                  }
                )
            });
          }
        );

      // Fetch language
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_language).get()
        .then(
          async function (response) {
            mask.language = await response.resolve();
          }
        );

      // Fetch tcaFields for existing core and mask fields
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_tca_fields).get()
        .then(
          async function (response) {
            mask.tcaFields = await response.resolve();
          }
        );

      // fetch tab declaratuins
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_tabs).get()
        .then(
          async function (response) {
            mask.tabs = await response.resolve();
          }
        );

      // fetch richtext configuration
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_richtext_configuration).get()
        .then(
          async function (response) {
            mask.global.richtextConfiguration = await response.resolve();
          }
        );

      // fetch CTypes
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_ctypes).get()
        .then(
          async function (response) {
            mask.global.ctypes = await response.resolve();
          }
        );

      Icons.getIcon('actions-edit-delete', Icons.sizes.small).done(function (icon) {
        mask.icons.delete = icon;
      });
      Icons.getIcon('actions-move-move', Icons.sizes.small).done(function (icon) {
        mask.icons.move = icon;
      });
      Icons.getIcon('actions-edit-pick-date', Icons.sizes.small).done(function (icon) {
        mask.icons.date = icon;
      });
      require(['TYPO3/CMS/Mask/FontIconPicker']);

      // TODO in v11 this is a regular event (no jquery)
      // Trigger input change on TYPO3 datepicker change event.
      $(document).on('formengine.dp.change', function () {
        document.querySelectorAll('.t3js-datetimepicker').forEach(function (input) {
          input.dispatchEvent((new Event('input')));
        });
      });
    },
    methods: {
      handleClone: function (item) {
        // Create a fresh copy of item
        let cloneMe = JSON.parse(JSON.stringify(item));
        this.$delete(cloneMe, 'uid');
        this.global.clonedField = cloneMe;
        return cloneMe;
      },
      /**
       * This adds a field by click on the field.
       * @param type
       */
      addField: function (type) {
        var newField = this.handleClone(type);
        var fields = this.fields;
        if (typeof this.global.activeField.parent.name !== 'undefined') {
          fields = this.global.activeField.parent.fields;
          newField.parent = this.global.activeField.parent;
        }
        var index = fields.indexOf(this.global.activeField) + 1;
        fields.splice(index, 0, newField);
        this.global.activeField = newField;
        this.global.currentTab = 'general';
      },
      onMove: function (e) {
        var draggedField = e.draggedContext.element.name;
        var depth = e.relatedContext.component.$parent.depth;
        var index = e.relatedContext.component.$parent.index;
        var parentName = '';

        if (depth > 0) {
          parentName = e.relatedContext.component.$parent.$parent.list[index].name;

          // Elements palette and tab are not allowed in palette
          if (['palette', 'tab'].includes(draggedField) && parentName === 'palette') {
            return false;
          }

          // Existing fields are not allowed as new inline field
          if (parentName === 'inline' && e.draggedContext.element.key !== '') {
            return false;
          }

          // Palettes or inline fields with elements are not allowed in inline fields
          if (parentName === 'inline' && ['palette', 'inline'].includes(draggedField) && e.draggedContext.element.fields.length > 0) {
            return false;
          }
        }

        // Linebreaks are only allowed in palette
        if (draggedField === 'linebreak' && parentName !== 'palette') {
          return false;
        }
      }
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
      },
      fieldTabs: function () {
        if (!this.global.activeField.name) {
          return [];
        }
        return this.tabs[this.global.activeField.name];
      },
      chooseFieldVisible: function () {
        if (!this.global.activeField.name) {
          return false;
        }
        if (this.global.activeField.name === 'inline') {
          return false;
        }
        if (this.global.activeField.parent.name === 'inline') {
          return false;
        }
        return this.availableTca[this.global.activeField.name].core.length > 0 || this.availableTca[this.global.activeField.name].mask.length > 0
      }
    }
  });
});
