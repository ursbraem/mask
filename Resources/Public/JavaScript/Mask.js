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
        maskPrefix: 'tx_mask_',
        mode: 'list',
        type: '',
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
          elementKeyAvailable: true,
          elementKey: false,
          elementLabel: false,
          emptyKeyFields: [],
          emptyGroupAllowedFields: [],
          emptyRadioItems: [],
          existingFieldKeyFields: []
        },
        global: {
          activeField: {},
          clonedField: {},
          richtextConfiguration: {},
          currentTab: 'general',
          ctypes: {}
        },
        loaded: false
      }
    },
    mounted: function () {
      const promises = [];

      // Fetch language
      const languageRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_language)).get()
        .then(
          async function (response) {
            mask.language = await response.resolve();
          }
        );

      // Fetch tcaFields for existing core and mask fields
      const tcaFieldsRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_tca_fields)).get()
        .then(
          async function (response) {
            mask.tcaFields = await response.resolve();
          }
        );

      // fetch tab declarations
      const tabsRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_tabs)).get()
        .then(
          async function (response) {
            mask.tabs = await response.resolve();
          }
        );

      // fetch richtext configuration
      const richtextConfigurationRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_richtext_configuration)).get()
        .then(
          async function (response) {
            mask.global.richtextConfiguration = await response.resolve();
          }
        );

      // fetch CTypes
      const ctypesRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_ctypes)).get()
        .then(
          async function (response) {
            const result = await response.resolve();
            mask.global.ctypes = result.ctypes;
          }
        );

      // fetch elements
      const elementsRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_elements)).get()
        .then(
          async function (response) {
            const result = await response.resolve();
            mask.elements = result.elements;
          }
        );

      // fetch fontawesome icons
      const iconsRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_icons)).get()
        .then(
          async function (response) {
            mask.faIcons = await response.resolve();
          }
        );

      const deleteIconRequest = Icons.getIcon('actions-edit-delete', Icons.sizes.small).done(function (icon) {
        mask.icons.delete = icon;
      });
      const moveIconRequest = Icons.getIcon('actions-move-move', Icons.sizes.small).done(function (icon) {
        mask.icons.move = icon;
      });
      const dateIconRequest = Icons.getIcon('actions-edit-pick-date', Icons.sizes.small).done(function (icon) {
        mask.icons.date = icon;
      });

      promises.push(languageRequest);
      promises.push(tcaFieldsRequest);
      promises.push(tabsRequest);
      promises.push(richtextConfigurationRequest);
      promises.push(ctypesRequest);
      promises.push(elementsRequest);
      promises.push(iconsRequest);
      promises.push(deleteIconRequest);
      promises.push(moveIconRequest);
      promises.push(dateIconRequest);

      Promise.all(promises).then(() => {
        mask.loaded = true;
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
      element: {
        handler() {
          this.validate();
        },
        deep: true
      },
      fields: {
        handler() {
          this.validate();
        },
        deep: true
      },
      'global.activeField.fields': function () {
        this.validate();
      },
      'element.key': function () {
        const validKey = this.checkAllowedCharacters(this.element.key);
        if (this.element.key !== validKey) {
          this.element.key = validKey;
          return;
        }
        new AjaxRequest(TYPO3.settings.ajaxUrls.mask_check_element_key)
          .withQueryArguments({key: this.element.key})
          .get()
          .then(
            async function (response) {
              const result = await response.resolve();
              mask.fieldErrors.elementKeyAvailable = result.isAvailable;
            }
          );
      },
      mode: function () {
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
        this.validate();
        if (!this.hasErrors) {
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
                  mask.getErrorFields().every(function (errorFields) {
                    if (errorFields.length > 0) {
                      mask.global.activeField = errorFields[0];
                      return false;
                    }
                    return true;
                  });
                }
              }
            ]
          )
        }
      },
      validateKey: function (field) {
        // Force mask prefix if not a core field
        if (!this.isCoreField && !this.hasMaskPrefix(field.key)) {
          field.key = this.maskPrefix;
          return;
        }

        // Force lowercase and remove special chars
        field.key = this.checkAllowedCharacters(field.key);

        // Skip empty fields (these are validated by empty validator)
        if (field.key === this.maskPrefix) {
          return;
        }

        // Step 1: Check if key is in current fields array
        let fields = this.getFields(field);
        let error = this.checkIfKeyExistsInFields(fields, this.global.activeField);
        if (error) {
          this.fieldErrors.existingFieldKeyFields.push(this.global.activeField);
        } else {
          mask.removeExistingKeyField(this.global.activeField);
        }

        // Step 2: Check if another field is now valid due to the change
        this.fieldErrors.existingFieldKeyFields.every(function (errorField) {
          if (errorField !== field && !mask.checkIfKeyExistsInFields(mask.getFields(errorField), errorField)) {
            mask.removeExistingKeyField(errorField);
          }
          return true;
        });

        // Step 3: Check if key is in possible tca array and avoid ajax check if so
        if (this.availableTcaKeys[field.name].includes(field.key)) {
          return;
        }

        // If there is an error already from step 1 or we are not on root, cancel tca ajax check
        if (error || !this.isRoot(field)) {
          return;
        }

        // Check if key already exists in table
        let arguments = {
          key: field.key,
          table: this.type,
          type: field.name,
          elementKey: ''
        };
        if (this.mode === 'edit') {
          arguments.elementKey = this.element.key;
        }
        new AjaxRequest(TYPO3.settings.ajaxUrls.mask_check_field_key)
          .withQueryArguments(arguments)
          .get()
          .then(
            async function (response) {
              const result = await response.resolve();
              if (result.isAvailable) {
                mask.removeExistingKeyField(mask.global.activeField);
              } else {
                mask.fieldErrors.existingFieldKeyFields.push(mask.global.activeField);
              }
            }
          );
      },
      hasMaskPrefix: function (key) {
        return key.substr(0, this.maskPrefix.length) === this.maskPrefix;
      },
      isRoot: function (field) {
        return this.isEmptyObject(field.parent) || field.parent.name === 'palette' && this.isEmptyObject(field.parent.parent);
      },
      getFields: function (field) {
        let fields = this.fields;
        if (!this.isRoot(field)) {
          if (field.parent.name !== 'palette' || !this.isEmptyObject(field.parent.parent)) {
            fields = field.parent.fields;
          } else {
            fields = field.parent.parent.fields;
          }
        }
        return fields;
      },
      checkIfKeyExistsInFields: function (fields, checkField) {
        let error = false;
        fields.every(function (field) {
          if (field !== checkField) {
            if (checkField.key === field.key) {
              error = true;
            } else {
              if (!error && field.name === 'palette') {
                error = mask.checkIfKeyExistsInFields(field.fields, checkField);
              }
            }
            return !error;
          }
          return true;
        });
        return error;
      },
      removeExistingKeyField: function (removedField) {
        mask.fieldErrors.existingFieldKeyFields = mask.fieldErrors.existingFieldKeyFields.filter(function (field) {
          return field !== removedField;
        });
      },
      openNew: function (type) {
        this.loaded = false;
        this.resetState();
        this.mode = 'new';
        this.type = type;
        if (this.type === 'tt_content') {
          this.element = this.getNewElement();
        }

        Promise.resolve(this.loadTca()).then(() => {
          mask.loaded = true;
        });
      },
      openEdit: function (type, element) {
        this.loaded = false;
        this.resetState();
        this.mode = 'edit';
        this.type = type;
        this.element = element;
        const tcaRequest = this.loadTca();

        // load element fields
        const elementRequest = (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_load_element))
          .withQueryArguments({
            type: type,
            key: element.key
          })
          .get()
          .then(
            async function (response) {
              const result = await response.resolve();
              mask.fields = result.fields;
            }
          );

        Promise.all([tcaRequest, elementRequest]).then(() => {
          mask.loaded = true;
        });
      },
      loadTca: function () {
        // Fetch fieldtypes and available tca
        return (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_fieldtypes)).get()
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
      },
      resetState: function () {
        this.type = '';
        this.element = {};
        this.fields = [];
        this.global.activeField = {};
        this.global.clonedField = {};
        this.fieldErrors = {
          elementKeyAvailable: true,
          elementKey: false,
          elementLabel: false,
          emptyKeyFields: [],
          emptyGroupAllowedFields: [],
          emptyRadioItems: [],
          existingFieldKeyFields: []
        };
      },
      fieldHasError: function (field) {
        if (!this.hasFieldErrors) {
          return false;
        }
        if (this.fieldErrors.emptyKeyFields.includes(field)) {
          return true;
        }
        if (this.fieldErrors.existingFieldKeyFields.includes(field)) {
          return true;
        }
        if (this.fieldErrors.emptyGroupAllowedFields.includes(field)) {
          return true;
        }
        if (this.fieldErrors.emptyRadioItems.includes(field)) {
          return true;
        }
      },
      validate: function () {
        this.fieldErrors.elementKey = this.element.key === '';
        this.fieldErrors.elementLabel = this.element.label === '';

        this.fieldErrors.emptyKeyFields = [];
        this.fieldErrors.emptyGroupAllowedFields = [];
        this.fieldErrors.emptyRadioItems = [];

        this.checkFieldKeyIsEmpty(this.fields);
        this.checkEmptyGroupAllowed(this.fields);
        this.checkEmptyRadioItems(this.fields);
      },
      getErrorFields: function () {
        return [
          this.fieldErrors.emptyKeyFields,
          this.fieldErrors.emptyGroupAllowedFields,
          this.fieldErrors.emptyRadioItems
        ];
      },
      checkFieldKeyIsEmpty: function (fields) {
        fields.every(function (item) {
          if (item.key === mask.maskPrefix) {
            mask.fieldErrors.emptyKeyFields.push(item);
          }
          if (item.fields.length > 0) {
            mask.checkFieldKeyIsEmpty(item.fields);
          }
          return true;
        });
      },
      checkEmptyGroupAllowed: function (fields) {
        fields.every(function (item) {
          if (item.tca['config.internal_type'] === 'db' && item.tca['config.allowed'] === '') {
            mask.fieldErrors.emptyGroupAllowedFields.push(item);
          }
          if (item.fields.length > 0) {
            mask.checkEmptyGroupAllowed(item.fields);
          }
          return true;
        });
      },
      checkEmptyRadioItems: function (fields) {
        fields.every(function (item) {
          if (item.name === 'radio' && item.tca['config.items'].split(',').length < 2) {
            mask.fieldErrors.emptyRadioItems.push(item);
          }
          if (item.fields.length > 0) {
            mask.checkEmptyRadioItems(item.fields);
          }
          return true;
        });
      },
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
        var parent = this.global.activeField.parent;
        var parentName = '';
        if (typeof parent !== 'undefined') {
          parentName = parent.name;
          newField.parent = parent;
          if (typeof parent.fields !== 'undefined') {
            fields = parent.fields;
          }
        } else {
          newField.parent = {};
        }
        if (this.validateMove(parentName, newField)) {
          var index = fields.indexOf(this.global.activeField) + 1;
          fields.splice(index, 0, newField);
          this.global.activeField = newField;
          this.global.currentTab = 'general';
          this.validateKey(newField);
        }
      },
      onMove: function (e) {
        const draggedField = e.draggedContext.element;
        const parent = e.relatedContext.component.$parent;
        const depth = parent.depth;
        const index = parent.index;
        let parentName = '';

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
          if (parentName === 'inline' && !draggedField.newField) {
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
      },
      checkAllowedCharacters: function (key) {
        key = key.toLowerCase();
        key = key.replace(/[^a-z0-9_]/g, '');
        return key;
      }
    },
    computed: {
      hasErrors: function () {
        return this.hasElementErrors || this.hasFieldErrors;
      },
      hasElementErrors: function () {
        return this.fieldErrors.elementKey || this.fieldErrors.elementLabel || !this.fieldErrors.elementKeyAvailable;
      },
      hasFieldErrors: function () {
        return this.fieldErrors.emptyKeyFields.length > 0
          || this.fieldErrors.emptyGroupAllowedFields.length > 0
          || this.fieldErrors.emptyRadioItems.length > 0
          || this.fieldErrors.existingFieldKeyFields.length > 0
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
      },
      keyFieldVisible: function () {
        return !['linebreak', 'palette', 'tab'].includes(this.global.activeField.name) && this.labelFieldVisible;
      },
      labelFieldVisible: function () {
        return !this.isCoreField && this.global.currentTab === 'general';
      },
      availableTcaKeys: function () {
        var keys = {};
        Object.keys(this.availableTca).forEach(function (key) {
          keys[key] = [];
          mask.availableTca[key].core.forEach(function (item) {
            keys[key].push(item.field);
          });
          mask.availableTca[key].mask.forEach(function (item) {
            keys[key].push(item.field);
          });
        });
        return keys;
      },
      activeFieldHasKeyError: function () {
          return this.fieldErrors.emptyKeyFields.includes(this.global.activeField)
          || this.fieldErrors.existingFieldKeyFields.includes(this.global.activeField)
      }
    }
  });
});
