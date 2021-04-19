define([
    'TYPO3/CMS/Mask/Contrib/vue',
    'TYPO3/CMS/Backend/Tooltip',
    'jquery'
  ],
  function (Vue, Tooltip, $) {
    return Vue.component(
      'fieldRow',
      {
        props: {
          global: Object,
          fields: Array,
          field: Object,
          language: Object,
          icons: Object,
          index: Number,
          loadMultiUse: Function,
          multiUseElements: Object
        },
        mounted: function () {
          Tooltip.initialize('[data-bs-toggle="tooltip"]', {
            delay: {
              'show': 50,
              'hide': 50
            },
            trigger: 'hover',
            container: '#mask'
          });
        },
        methods: {
          hideTooltip() {
            Tooltip.hide($(this.$refs['row' + this.index]));
          },
          keyWithoutMask: function (key) {
            if (key.substr(0, 8) === this.global.maskPrefix) {
              return key.substr(8);
            } else {
              return key;
            }
          }
        },
        computed: {
          isMultiUse: function () {
            return (typeof this.multiUseElements[this.field.key] !== 'undefined') && this.multiUseElements[this.field.key].length;
          }
        },
        template: `
    <div class="mask-field__row" @click="global.activeField = field; global.currentTab = 'general'; loadMultiUse();">
        <i v-if="isMultiUse" class="mask-field__multiuse fa fa-info-circle"></i>
        <div class="mask-field__image">
            <div v-html="field.icon"></div>
        </div>
        <div class="mask-field__body">
          <div class="mask-field__text">
            <span v-if="field.name == 'linebreak'" class="mask-field__label">Linebreak</span>
            <span v-else class="mask-field__label">{{ field.label }}</span>
            <span class="mask-field__key" v-if="!global.sctructuralFields.includes(field.name)">{{ keyWithoutMask(field.key) }}</span>
          </div>
          <div class="mask-field__actions">
              <a class="btn btn-default btn-sm" @click.stop="$emit('remove-field', index); hideTooltip();" data-bs-toggle="tooltip" :data-title="language.tooltip.deleteField" v-html="icons.delete" :ref="'row' + index"></a>
          </div>
        </div>
    </div>
        `
      }
    )
  }
);
