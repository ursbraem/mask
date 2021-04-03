define([
    'TYPO3/CMS/Mask/Contrib/vue',
    'TYPO3/CMS/Mask/Contrib/vuedraggable',
    'TYPO3/CMS/Mask/Components/FieldRow',
  ],
  function (Vue, draggable, fieldRow) {
    return Vue.component(
      'nested-draggable',
      {
        props: {
          fields: Array,
          icons: Object,
          global: Object,
          depth: Number,
          index: Number,
          move: Function,
          fieldHasError: Function,
          validateKey: Function,
          language: Object
        },
        components: {
          draggable,
          fieldRow
        },
        methods: {
          uuid(e) {
            if (e.uid) {
              return e.uid;
            }
            const key = Math.random()
              .toString(16)
              .slice(2);

            this.$set(e, 'uid', key);
            // Auto set key on structural fields
            if ((e.key === this.global.maskPrefix || e.key === '') && this.global.sctructuralFields.includes(e.name)) {
              this.$set(e, 'key', this.global.maskPrefix + key);
            }
            return e.uid;
          },
          onAdd: function () {
            this.global.activeField = this.global.clonedField;
            this.global.currentTab = 'general';
            if (this.depth > 0) {
              this.global.activeField.parent = this.$parent.list[this.index];
            } else {
              this.global.activeField.parent = {};
            }
            this.validateKey(this.global.activeField);
          },
          removeField: function (index) {
            if (this.fields[index - 1]) {
              this.global.activeField = this.fields[index - 1];
            } else if (this.fields[index + 1]) {
              this.global.activeField = this.fields[index + 1];
            }
            this.global.deletedFields.push(this.fields[index]);
            this.fields.splice(index, 1);
            if (this.fields.length === 0) {
              if (this.depth > 0) {
                this.$emit('set-parent-active', this.index);
              } else {
                this.global.activeField = {};
              }
            }
            // Reset current tab
            this.global.currentTab = 'general';
          },
          setParentActive(index) {
            this.global.activeField = this.fields[index];
          },
          isParentField: function (field) {
            return ['inline', 'palette'].includes(field.name);
          }
        },
        template: `
<draggable
    tag="ul"
    class="tx_mask_fieldtypes dragtarget"
    :list="fields"
    group="fieldTypes"
    ghost-class="ghost"
    @add="onAdd"
    :move="move"
  >
  <li v-for="(field, index) in fields" :key="uuid(field)" class="mask-field" :class="[{active: global.activeField == field}, 'id_' + field.name, {'has-error': fieldHasError(field)}]">
    <field-row
        :global="global"
        :fields="fields"
        :field="field"
        :language="language"
        :icons="icons"
        :index="index"
        @remove-field="removeField($event)"
    ></field-row>
    <div class="tx_mask_btn_caption" v-if="isParentField(field)">
        <nested-draggable
            @set-parent-active="setParentActive($event)"
            :depth="depth + 1"
            :index="index"
            :fields="field.fields"
            :icons="icons"
            :global="global"
            :move="move"
            :field-has-error="fieldHasError"
            :validate-key="validateKey"
            :language="language"
          />
    </div>
  </li>
</draggable>
        `
      }
    )
  }
);
