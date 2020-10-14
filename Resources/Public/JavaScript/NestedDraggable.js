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

            return e.uid;
          },
          removeField: function (index) {
            this.fields.splice(index, 1);
          }
        },
        template: `
<draggable
  tag="ul"
  class="tx_mask_fieldtypes dragtarget"
  :list="fields"
  group="fieldTypes"
  ghost-class="ghost"
  >
  <li v-for="(field, index) in fields" :key="uuid(field)" class="tx_mask_btn">
    <div class="tx_mask_btn_row">
        <div class="tx_mask_btn_img">
            <div v-html="field.icon"></div>
        </div>
        <div class="tx_mask_btn_actions">
            <span @click="removeField(index)" class="id_delete" title="Delete item" v-html="icons.delete"></span>
            <span class="id_move" title="Move item" v-html="icons.move"></span>
        </div>
    </div>
    <div class="tx_mask_btn_caption">
        <nested-draggable :fields="field.fields" :icons="icons"/>
    </div>
  </li>
</draggable>
        `
      }
    )
  }
);
