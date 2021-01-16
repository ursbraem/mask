define([
  'TYPO3/CMS/Mask/Contrib/vue',
  'TYPO3/CMS/Mask/Contrib/vuedraggable',
  'TYPO3/CMS/Mask/Components/NestedDraggable',
  'TYPO3/CMS/Mask/Components/FormField',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Backend/Icons',
  'TYPO3/CMS/Backend/Modal',
  'TYPO3/CMS/Backend/Severity'
], function (
  Vue,
  draggable,
  nestedDraggable,
  formField,
  AjaxRequest,
  Icons,
  Modal,
  Severity
) {
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
        mode: 'list',
        type: 'tt_content',
        elements: [],
        element: {},
        fieldTypes: [],
        tcaFields: {},
        tabs: {},
        fields: [],
        language: [],
        icons: {},
        faIcons: {},
        availableTca: {},
        fieldErrors: {
          elementKey: false,
          elementLabel: false,
          emptyFieldKey: {},
          emptyGroupAllowedField: {},
          emptyRadioItems: {}
        },
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
              new AjaxRequest(TYPO3.settings.ajaxUrls.mask_existing_tca).withQueryArguments({table: mask.type, type: item.name}).get()
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

      // fetch elements
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_elements).get()
        .then(
          async function (response) {
            mask.elements = await response.resolve();
          }
        );

      // fetch fontawesome icons
      new AjaxRequest(TYPO3.settings.ajaxUrls.mask_icons).get()
        .then(
          async function (response) {
            mask.faIcons = await response.resolve();
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

      // TODO in v11 this is a regular event (no jquery)
      // Trigger input change on TYPO3 datepicker change event.
      $(document).on('formengine.dp.change', function () {
        document.querySelectorAll('.t3js-datetimepicker').forEach(function (input) {
          input.dispatchEvent((new Event('input')));
        });
      });
    },
    watch: {
      mode: function () {
        if (this.mode === 'edit') {
          // load element fields
          new AjaxRequest(TYPO3.settings.ajaxUrls.mask_load_element)
            .withQueryArguments({
              type: mask.type,
              key: mask.element.key
            })
            .get()
            .then(
              async function (response) {
                mask.fields = await response.resolve();
              }
            );
        } else {
          mask.fields = [];
        }

        if (this.maskBuilderOpen) {
          // Boot font icon picker
          require(['jquery', 'TYPO3/CMS/Mask/Contrib/FontIconPicker'], function ($) {
            var iconPicker = $('#meta_icon').fontIconPicker({
              source: mask.faIcons
            });
            iconPicker.setIcon(mask.element.icon);
          });
        }
      }
    },
    methods: {
      save: function () {
        if (this.validate()) {
          const payload = {
            element: this.element,
            fields: this.fields,
            type: this.type,
            isNew: this.mode === 'new'
          };
          new AjaxRequest(TYPO3.settings.ajaxUrls.mask_save).post(payload)
            .then(
              async function (response) {
                const res = await response.resolve();
              }
            );
        } else {
          Modal.confirm(
            this.language.alert || 'Alert',
            this.language.fieldsMissing,
            Severity.error,
            [
              {
                text: this.language.ok || 'OK',
                btnClass: 'btn-default',
                active: true,
                name: 'ok',
                trigger: function () {
                  Modal.dismiss();
                }
              }
            ]
          )
        }
      },
      validate: function () {
        this.fieldErrors.elementKey = this.element.key === '';
        this.fieldErrors.elementLabel = this.element.label === '';

        this.fieldErrors.emptyFieldKey = {};
        this.fieldErrors.emptyGroupAllowedField = {};
        this.fieldErrors.emptyRadioItems = {};
        this.checkFieldKeyIsEmpty(this.fields);
        this.checkEmptyGroupAllowed(this.fields);
        this.checkEmptyRadioItems(this.fields);

        return !this.hasErrors;
      },
      checkFieldKeyIsEmpty: function (fields) {
        fields.every(function (item) {
          if (item.key === '') {
            mask.fieldErrors.emptyFieldKey = item;
            mask.global.activeField = item;
            return false;
          }
          if (item.fields.length > 0) {
            return mask.checkFieldKeyIsEmpty(item.fields);
          }
          return true;
        });
      },
      checkEmptyGroupAllowed: function (fields) {
        fields.every(function (item) {
          if (item.tca['config.internal_type'] === 'db' && item.tca['config.allowed'] === '') {
            mask.fieldErrors.emptyGroupAllowedField = item;
            mask.global.activeField = item;
            return false;
          }
          if (item.fields.length > 0) {
            return mask.checkEmptyGroupAllowed(item.fields);
          }
          return true;
        });
      },
      checkEmptyRadioItems: function (fields) {
        fields.every(function (item) {
          if (item.name === 'radio' && item.tca['config.items'].split(',').length < 2) {
            mask.fieldErrors.emptyRadioItems = item;
            mask.global.activeField = item;
            return false;
          }
          if (item.fields.length > 0) {
            return mask.checkEmptyRadioItems(item.fields);
          }
          return true;
        });
      },
      handleClone: function (item) {
        // Create a fresh copy of item
        let cloneMe = JSON.parse(JSON.stringify(item));
        this.$delete(cloneMe, 'uid');
        cloneMe['newField'] = true;
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
        var parent = this.global.activeField.parent;
        var parentName = '';
        if (typeof parent !== 'undefined') {
          parentName = parent.name;
          newField.parent = parent;
          if (typeof parent.fields !== 'undefined') {
            fields = parent.fields;
          }
        }
        if (this.validateMove(parentName, newField)) {
          var index = fields.indexOf(this.global.activeField) + 1;
          fields.splice(index, 0, newField);
          this.global.activeField = newField;
          this.global.currentTab = 'general';
        }
      },
      onMove: function (e) {
        var draggedField = e.draggedContext.element;
        var parent = e.relatedContext.component.$parent;
        var depth = parent.depth;
        var index = parent.index;
        var parentName = '';

        if (depth > 0) {
          parentName = parent.$parent.list[index].name;
        }

        return this.validateMove(parentName, draggedField);
      },
      validateMove: function (parentName, draggedField) {
        if (parentName !== '') {
          // Elements palette and tab are not allowed in palette
          if (['palette', 'tab'].includes(draggedField.name) && parentName === 'palette') {
            return false;
          }

          // Existing fields are not allowed as new inline field
          if (parentName === 'inline' && draggedField.key !== '') {
            return false;
          }

          // Palettes or inline fields with elements are not allowed in inline fields
          if (parentName === 'inline' && ['palette', 'inline'].includes(draggedField.name) && draggedField.fields.length > 0) {
            return false;
          }
        }

        // Linebreaks are only allowed in palette
        if (draggedField.name === 'linebreak' && parentName !== 'palette') {
          return false;
        }

        return true;
      },
      getNewElement: function () {
        return JSON.parse(JSON.stringify({
          key: '',
          label: '',
          shortLabel: '',
          description: '',
          icon: '',
          color: '#000000'
        }));
      },
      isEmptyObject: function (obj) {
        return Object.keys(obj).length === 0 && obj.constructor === Object;
      }
    },
    computed: {
      hasErrors: function () {
        return this.hasElementErrors || this.hasFieldErrors;
      },
      hasElementErrors: function () {
        return this.fieldErrors.elementKey || this.fieldErrors.elementLabel;
      },
      hasFieldErrors: function () {
        return !this.isEmptyObject(this.fieldErrors.emptyFieldKey)
          || !this.isEmptyObject(this.fieldErrors.emptyGroupAllowedField)
          || !this.isEmptyObject(this.fieldErrors.emptyRadioItems);
      },
      maskBuilderOpen: function () {
        return this.mode === 'edit' || this.mode === 'new';
      },
      isCoreField: function () {
        if (this.isEmptyObject(this.global.activeField)) {
          return false;
        }
        var isExisting = false;
        this.availableTca[this.global.activeField.name].core.forEach(function (item) {
          if (item.field === mask.global.activeField.key) {
            isExisting = true;
          }
        });
        return isExisting;
      },
      isExistingMaskField: function () {
        if (this.isEmptyObject(this.global.activeField)) {
          return false;
        }
        var isExisting = false;
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
        if (this.isEmptyObject(this.global.activeField)) {
          return false;
        }
        if (!this.global.activeField.newField && !this.isCoreField) {
          return false;
        }
        if (['inline', 'palette', 'linebreak', 'tab'].includes(this.global.activeField.name)) {
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
