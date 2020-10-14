import Vue from 'vue';

define([], function () {
  var mask = new Vue({
    el: '#mask',
    data: {
      fieldTypes: [
        {
          name: 'String',
        },
        {
          name: 'Integer',
        },
        {
          name: 'Float',
        },
        {
          name: 'Link',
        },
        {
          name: 'Date',
        }
      ]
    },
    template: `
      <div>
      <div class="row bench">
        <div class="form-group col-sm-2 tx_mask_borderright tx_mask_tabcell1">
          <h1>
            Header
          </h1>
          partial="WizardContent/Metadata" arguments="{storage:storage, icons: icons}
          <div>
            partial="General/Controls"
          </div>
        </div>
        <div class="col-sm-4 tx_mask_tabcell4">
          <label class="t3js-formengine-label">
            key="tx_mask.all.add_new_fields"
          </label>
          <div class="col-sm-12 tx_mask_tabcell5">
            <ul class="tx_mask_fieldtypes tx_mask_field_templates" id="dragstart">

            </ul>
          </div>
          <label class="t3js-formengine-label">
            key="tx_mask.all.fields_of_element"
          </label>
          <div class="form-group col-sm-12 tx_mask_borderright tx_mask_tabcell2">
            <ul class="tx_mask_fieldtypes dragtarget"></ul>
          </div>
        </div>
        <div class="form-group col-sm-6 tx_mask_tabcell3"></div>
      </div>
      </div>
    `
  });
});
