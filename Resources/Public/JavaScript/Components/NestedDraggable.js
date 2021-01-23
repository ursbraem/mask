define([
    'TYPO3/CMS/Mask/Contrib/vue',
    'TYPO3/CMS/Mask/Contrib/vuedraggable'
  ],
  function (Vue, draggable) {
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
          validateKey: Function
        },
        components: {
          draggable
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
            if (['linebreak', 'palette', 'tab'].includes(e.name)) {
              this.$set(e, 'key', 'tx_mask_' + key);
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
          },
          keyWithoutMask: function (key) {
            if (key.substr(0, 8) === 'tx_mask_') {
              return key.substr(8);
            } else {
              return key;
            }
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
  <li v-for="(field, index) in fields" :key="uuid(field)" :class="['tx_mask_btn', {active: global.activeField == field}, 'id_' + field.name, {'has-error': fieldHasError(field)}]">
    <div class="tx_mask_btn_row" @click="global.activeField = field; global.currentTab = 'general'">
        <div class="tx_mask_btn_img">
            <div v-html="field.icon"></div>
        </div>
        <div class="tx_mask_btn_text">
          <span v-if="field.name == 'linebreak'" class="id_labeltext">Linebreak</span>
          <span v-else class="id_labeltext">{{ field.label }}</span>
          <span class="id_keytext" v-if="field.name != 'linebreak'">{{ keyWithoutMask(field.key) }}</span>
        </div>
        <div class="tx_mask_btn_actions">
            <span @click.stop="removeField(index)" class="id_delete" title="Delete item" v-html="icons.delete"></span>
            <span class="id_move" title="Move item" v-html="icons.move"></span>
        </div>
    </div>
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
          />
    </div>
  </li>
</draggable>
        `
      }
    )
  }
);
