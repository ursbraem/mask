define([
      'TYPO3/CMS/Mask/Contrib/vue',
      'jquery',
      'TYPO3/CMS/Mask/Contrib/FontIconPicker'
    ],
    function (Vue, $) {
      return Vue.component(
          'font-icon-picker',
          {
            props: {
              element: Object,
              language: Object,
              faIcons: Object
            },
            data() {
              return {
                iconPicker: {}
              }
            },
            mounted() {
              const iconPicker = $('#meta_icon').fontIconPicker({
                source: this.faIcons
              });
              iconPicker.setIcon(this.element.icon);
              this.iconPicker = $(iconPicker[0]).data('fontIconPicker');
            },
            template: `
    <div class="col-sm-6">
        <label class="t3js-formengine-label" for="meta_icon">
            <a href="https://fortawesome.github.io/Font-Awesome/icons/" target="_blank">
                {{ language.icon }}
            </a>
        </label>
        <div class="t3js-formengine-field-item icon-field">
            <div class="form-control-wrap">
                <select id="meta_icon"></select>
            </div>
        </div>
    </div>
        `
          }
      )
    }
);
