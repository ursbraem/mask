define([
    'TYPO3/CMS/Mask/Contrib/vue',
    'TYPO3/CMS/Backend/Tooltip',
    'jquery'
  ],
  function (Vue, Tooltip, $) {
    return Vue.component(
      'field',
      {
        props: {
          type: Object,
          addField: Function,
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
            Tooltip.hide($(this.$refs[this.type.name]));
          },
        },
        template: `
          <li @click="addField(type);" class="mask-field mask-field--selectable">
              <div class="mask-field__row">
                  <div @mousedown="hideTooltip()" class="mask-field__image" v-html="type.icon" data-bs-toggle="tooltip" :data-title="type.itemLabel" :ref="type.name"></div>
              </div>
          </li>
        `
      }
    )
  }
);
