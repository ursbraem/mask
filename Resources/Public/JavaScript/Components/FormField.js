define([
    'TYPO3/CMS/Mask/Contrib/vue',
    'jquery'
  ],
  function (Vue, $) {
    return Vue.component(
      'form-field',
      {
        props: {
          column: Number,
          tcaFields: Object,
          tcaKey: String,
          global: Object,
          icons: Object
        },
        data: function () {
          return {
            documentation: 'https://docs.typo3.org/m/typo3/reference-tca/10.4/en-us/'
          }
        },
        beforeMount: function () {
          // Load richtextConfiguration with presets
          if (this.tcaKey === 'config.richtextConfiguration') {
            this.tcaFields[this.tcaKey].items = this.global.richtextConfiguration;
          }
        },
        mounted: function () {
          // Initialize datepicker.
          if ((this.tcaKey in this.$refs) && this.$refs[this.tcaKey].classList.contains('t3js-datetimepicker')) {
            // This is for dev mode only.
            if (!TYPO3.settings.DateTimePicker) {
              TYPO3.settings.DateTimePicker = JSON.parse('{"DateFormat":["DD-MM-YYYY","HH:mm DD-MM-YYYY"]}');
            }
            require(['TYPO3/CMS/Backend/DateTimePicker'], function (DateTimePicker) {
              DateTimePicker.initialize(this.$refs[this.tcaKey]);
            }.bind(this));
          }
        },
        methods: {
          switchDependsOn: function (tcaKey, dependsOn) {
            if (!!dependsOn && this.global.activeField.tca[tcaKey] === this.valueOn) {
              this.global.activeField.tca[dependsOn] = 1;
            }
          },
          checkPrefixLangTitle: function (key) {
            if (key !== 'prefixLangTitle') {
              return true;
            }
            return ['string', 'text', 'richtext'].includes(this.global.activeField.name);
          },
          focusDatePicker: function (key) {
            this.$refs[key].focus();
          },
        },
        computed: {
          field: function () {
            return this.tcaFields[this.tcaKey];
          },
          valueOn: function () {
            return 'valueOn' in this.field ? this.field.valueOn : 1;
          },
          valueOff: function () {
            return 'valueOff' in this.field ? this.field.valueOff : 0;
          },
          type: function () {
            if (this.field.type !== 'variable') {
              return this.field.type;
            }

            if ('types' in this.field) {
              if (this.global.activeField.name in this.field.types) {
                return this.field.types[this.global.activeField.name];
              }
              return 'text';
            }

            var formFieldMap = {
              'integer': 'number',
              'float': 'number',
              'date': 'date',
              'datetime': 'date',
              'timestamp': 'date',
              'text': 'textarea',
              'richtext': 'textarea'
            };
            if (this.global.activeField.name in formFieldMap) {
              return formFieldMap[this.global.activeField.name];
            }
            return 'text';
          },
          dateType: function () {
            if (['date', 'datetime'].includes(this.global.activeField.name)) {
              return this.global.activeField.name;
            }
            if (this.global.activeField.name === 'timestamp') {
              return this.global.activeField.tca['config.eval'];
            }
            return 'date';
          }
        },
        template: `
          <div :class="['form-group', 'col-sm-' + column]">
            <label class="t3js-formengine-label" :for="tcaKey">
                {{ field.label }}
            </label>
            <code>[{{ field.code }}]</code><a v-if="!!field.documentation" :href="documentation + field.documentation" target="_blank" title="Show option in official TYPO3 documentation"><i class="fa fa-question-circle"/></a>
            <div class="t3js-formengine-field-item">
              <span class="formengine-field-item-description text-muted" v-if="field.description">{{ field.description }}</span>
              <div v-if="type == 'text'" class="form-control-wrap">
                <input :id="tcaKey" class="form-control" :placeholder="field.placeholder" v-model="global.activeField.tca[tcaKey]">
              </div>
              <div v-if="type == 'textarea'" class="form-control-wrap">
                <textarea :id="tcaKey" class="form-control" :placeholder="field.placeholder" :rows="field.rows" v-model="global.activeField.tca[tcaKey]"></textarea>
              </div>
              <div v-if="type == 'number'" class="form-control-wrap">
                <input v-model="global.activeField.tca[tcaKey]" :id="tcaKey" :placeholder="field.placeholder" :min="field.min" :max="field.max" :step="field.step" class="form-control" type="number">
              </div>
              <div v-if="type == 'checkbox'" class="form-control-wrap">
                <div :class="['checkbox', 'checkbox-type-toggle', {'checkbox-invert': field.invert}]">
                    <input :id="tcaKey" v-model="global.activeField.tca[tcaKey]" type="checkbox" class="checkbox-input" :true-value="valueOn" :false-value="valueOff" @change="switchDependsOn(tcaKey, field.dependsOn)">
                    <label class="checkbox-label" :for="tcaKey">
                        <span class="checkbox-label-text">[{{ global.activeField.tca[tcaKey] ? global.activeField.tca[tcaKey] : 0 }}]</span>
                    </label>
                </div>
              </div>
              <div v-if="type == 'select'" class="form-control-wrap">
                <select v-model="global.activeField.tca[tcaKey]" class="form-control">
                    <option v-for="(item, key) in field.items" :value="key">{{ item }} <span v-if="key !== ''">[{{ key }}]</span></option>
                </select>
              </div>
              <div v-if="type == 'date'" class="form-control-wrap">
                <div class="input-group">
                  <input v-model="global.activeField.tca[tcaKey]" :ref="tcaKey" :data-date-type="dateType" class="t3js-datetimepicker form-control t3js-clearable">
                  <span class="input-group-btn">
                      <label @click="focusDatePicker(tcaKey)" class="btn btn-default" v-html="icons.date"></label>
                  </span>
                </div>
              </div>
              <div class="form-wizards-wrap" v-if="type == 'radio'">
                  <div v-if="checkPrefixLangTitle(value)" class="radio" v-for="(label, value) in field.items">
                      <label>
                          <input type="radio" v-model="global.activeField.tca[tcaKey]" :value="value"> {{ label }} <span v-if="value !== ''">[{{ value }}]</span></option>
                      </label>
                  </div>
              </div>
            </div>
            <div v-if="type == 'cTypes'" class="form-control-wrap">
              <select v-model="global.activeField.tca[tcaKey]" class="form-control" multiple="multiple" size="20">
                <option v-for="(item, key) in global.ctypes" :value="key">{{ item }} <span v-if="key !== ''">[{{ key }}]</span></option>
              </select>
            </div>
          </div>
        `
      }
    )
  }
);
