define([
      'TYPO3/CMS/Mask/Contrib/vue',
      'TYPO3/CMS/Backend/Icons',
      'TYPO3/CMS/Core/Ajax/AjaxRequest',
      'TYPO3/CMS/Backend/Tooltip',
      'jquery'
    ],
    function (Vue, Icons, AjaxRequest, Tooltip, $) {
      return Vue.component(
          'button-bar',
          {
            props: {
              element: Object,
              showMessages: Function,
              icons: Object,
              openEdit: Function,
              openDeleteDialog: Function,
              language: Object
            },
            data() {
              return {
                toggleIcons: {
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
              },
              hideTooltip(key) {
                Tooltip.hide($(this.$refs[this.element.key + key]));
              },
            },
            computed: {
              toggleIcon() {
                if (this.loading) {
                  return this.toggleIcons.spinnerCircleDark;
                }
                return this.element.hidden ? this.toggleIcons.actionsEditUnhide : this.toggleIcons.actionsEditHide;
              }
            },
            mounted() {
              Icons.getIcon('actions-edit-hide', Icons.sizes.small).done((icon) => {
                this.toggleIcons.actionsEditHide = icon;
              });
              Icons.getIcon('actions-edit-unhide', Icons.sizes.small).done((icon) => {
                this.toggleIcons.actionsEditUnhide = icon;
              });
              Icons.getIcon('spinner-circle-dark', Icons.sizes.small).done((icon) => {
                this.toggleIcons.spinnerCircleDark = icon;
              });

              Tooltip.initialize('[data-bs-toggle="tooltip"]', {
                delay: {
                  'show': 50,
                  'hide': 50
                },
                trigger: 'hover',
                container: '#mask'
              });
            },
            template: `
              <div class="mask-elements__btn-group btn-group">
                <a :ref="element.key + 'edit'" class="btn btn-default" @click="hideTooltip('edit'); openEdit('tt_content', element);" data-bs-toggle="tooltip" :data-title="language.tooltip.editElement">
                    <span v-html="icons.edit"></span>
                </a>
                <a v-show="!element.hidden" :ref="element.key + 'hide'" class="btn btn-default" :class="{'disable-pointer': loading}" @click="hideTooltip('hide'); toggleVisibility('hide');" data-bs-toggle="tooltip" :data-title="language.tooltip.disableElement">
                   <span v-html="toggleIcon"></span>
                </a>
                <a v-show="element.hidden" :ref="element.key + 'enable'" class="btn btn-default" :class="{'disable-pointer': loading}" @click="hideTooltip('enable'); toggleVisibility('enable');" data-bs-toggle="tooltip" :data-title="language.tooltip.enableElement">
                   <span v-html="toggleIcon"></span>
                </a>
                <a :ref="element.key + 'delete'" class="btn btn-default" @click="hideTooltip('delete'); openDeleteDialog(element)" data-bs-toggle="tooltip" :data-title="language.tooltip.deleteElement">
                    <span v-html="icons.delete"></span>
                </a>
              </div>
        `
          }
      )
    }
);
