define([
      'TYPO3/CMS/Mask/Contrib/vue',
      'TYPO3/CMS/Backend/Icons',
      'TYPO3/CMS/Core/Ajax/AjaxRequest',
    ],
    function (Vue, Icons, AjaxRequest) {
      return Vue.component(
          'hide-button',
          {
            props: {
              element: Object,
              showMessages: Function
            },
            data() {
              return {
                icons: {
                  actionsEditHide: '',
                  actionsEditUnhide: '',
                  spinnerCircleDark: '',
                },
                loading: false
              };
            },
            methods: {
              toggleVisibility() {
                this.loading = true;
                (new AjaxRequest(TYPO3.settings.ajaxUrls.mask_toggle_visibility)).post({element: this.element})
                    .then(
                        async response => {
                          const res = await response.resolve();
                          this.loading = false;
                          this.showMessages(res);
                          this.$emit('toggle');
                        }
                    )
              }
            },
            computed: {
              icon() {
                if (this.loading) {
                  return this.icons.spinnerCircleDark;
                }
                return this.element.hidden ? this.icons.actionsEditUnhide : this.icons.actionsEditHide;
              }
            },
            mounted() {
              Icons.getIcon('actions-edit-hide', Icons.sizes.small).done((icon) => {
                this.icons.actionsEditHide = icon;
              });
              Icons.getIcon('actions-edit-unhide', Icons.sizes.small).done((icon) => {
                this.icons.actionsEditUnhide = icon;
              });
              Icons.getIcon('spinner-circle-dark', Icons.sizes.small).done((icon) => {
                this.icons.spinnerCircleDark = icon;
              });
            },
            template: `
              <a class="btn btn-default" @click="toggleVisibility">
                 <span v-html="icon"></span>
              </a>
        `
          }
      )
    }
);
