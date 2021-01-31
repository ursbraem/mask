<?php

use MASK\Mask\DataStructure\FieldType;

return [
    FieldType::STRING => [
        'tca_in' => [
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config.type' => 'input',
        ],
        'sql' => 'varchar(255) DEFAULT \'\' NOT NULL'
    ],
    FieldType::FLOAT => [
        'tca_in' => [
            'config.eval.null' => 0,
        ],
        'tca_out' => [
            'config.type' => 'input',
            'config.eval.double2' => 1
        ],
        'sql' => 'double(11,2) DEFAULT \'0.00\' NOT NULL'
    ],
    FieldType::INTEGER => [
        'tca_in' => [
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config.type' => 'input',
            'config.eval.int' => 1
        ],
        'sql' => 'int(11) DEFAULT \'0\' NOT NULL'
    ],
    FieldType::LINK => [
        'tca_in' => [
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config.type' => 'input',
            'config.renderType' => 'inputLink',
            'softref' => 'typolink',
            'fieldControl.linkPopup.options.title' => 'Link',
        ],
        'sql' => 'varchar(1024) DEFAULT \'\' NOT NULL'
    ],
    FieldType::DATE => [
        'tca_in' => [
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config.type' => 'input',
            'config.dbType' => 'date',
            'config.renderType' => 'inputDateTime',
            'config.eval.date' => 1
        ],
        'sql' => 'date'
    ],
    FieldType::DATETIME => [
        'tca_in' => [
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config.type' => 'input',
            'config.dbType' => 'datetime',
            'config.renderType' => 'inputDateTime',
            'config.eval.datetime' => 1
        ],
        'sql' => 'datetime'
    ],
    FieldType::TIMESTAMP => [
        'tca_in' => [
            'config.eval' => 'date',
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config.type' => 'input',
            'config.renderType' => 'inputDateTime',
            'config.eval.int' => 1
        ],
        'sql' => 'int(10) unsigned DEFAULT \'0\' NOT NULL'
    ],
    FieldType::TEXT => [
        'tca_in' => [
            'config.wrap' => 'virtual',
            'config.format' => '',
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config.type' => 'text',
        ],
        'sql' => 'mediumtext'
    ],
    FieldType::RICHTEXT => [
        'tca_in' => [
            'config.richtextConfiguration' => ''
        ],
        'tca_out' => [
            'config.type' => 'text',
            'config.enableRichtext' => 1,
            'config.softref' => 'rtehtmlarea_images,typolink_tag,images,email[subst],url'
        ],
        'sql' => 'mediumtext'
    ],
    FieldType::CHECK => [
        'tca_in' => [
            'config.renderType' => ''
        ],
        'tca_out' => [
            'config.type' => 'check'
        ],
        'sql' => 'tinyint(2) DEFAULT \'0\' NOT NULL'
    ],
    FieldType::SELECT => [
        'tca_in' => [
            'config.renderType' => 'selectSingle'
        ],
        'tca_out' => [
            'config.type' => 'select'
        ],
        'sql' => 'varchar(255) DEFAULT \'\' NOT NULL'
    ],
    FieldType::RADIO => [
        'tca_in' => [
            'config.items' => ''
        ],
        'tca_out' => [
            'config.type' => 'radio'
        ],
        'sql' => 'int(11) DEFAULT \'0\' NOT NULL'
    ],
    FieldType::GROUP => [
        'tca_in' => [
            'config.internal_type' => 'db',
            'config.allowed' => '',
            'config.fieldControl.editPopup' => 1,
            'config.fieldControl.addRecord' => 1,
            'config.fieldControl.listModule' => 1,
        ],
        'tca_out' => [
            'config.type' => 'group'
        ],
        'sql' => 'text'
    ],
    FieldType::FILE => [
        'tca_in' => [
            'imageoverlayPalette' => 1,
            'config.appearance.fileUploadAllowed' => 1
        ],
        'tca_out' => [
            'options' => 'file'
        ],
        'sql' => 'int(11) unsigned DEFAULT \'0\' NOT NULL'
    ],
    FieldType::INLINE => [
        'tca_in' => [
            'config.appearance.levelLinksPosition' => 'top',
            'config.appearance.showPossibleLocalizationRecords' => 1,
            'config.appearance.showAllLocalizationLink' => 1,
            'config.appearance.showRemovedLocalizationRecords' => 1
        ],
        'tca_out' => [
            'config.type' => 'inline',
            'config.foreign_table' => '--inlinetable--',
            'config.foreign_field' => 'parentid',
            'config.foreign_table_field' => 'parenttable',
            'config.foreign_sortby' => 'sorting',
            'config.appearance.enabledControls.dragdrop' => 1
        ],
        'sql' => 'int(11) unsigned DEFAULT \'0\' NOT NULL'
    ],
    FieldType::CONTENT => [
        'tca_in' => [
            'config.appearance.levelLinksPosition' => 'top'
        ],
        'tca_out' => [
            'config.type' => 'inline',
            'config.foreign_table' => 'tt_content',
            'config.overrideChildTca.columns.colPos.config.default' => 999,
            'config.foreign_sortby' => 'sorting',
            'config.appearance.collapseAll' => 1,
            'config.appearance.levelLinksPosition' => 'top',
            'config.appearance.showPossibleLocalizationRecords' => 1,
            'config.appearance.showAllLocalizationLink' => 1,
            'config.appearance.showRemovedLocalizationRecords' => 1,
            'config.appearance.useSortable' => 1,
            'config.appearance.enabledControls.dragdrop' => 1
        ],
        'sql' => 'int(11) unsigned DEFAULT \'0\' NOT NULL'
    ],
    FieldType::TAB => [
        'tca_out' => [
            'config.type' => 'tab'
        ]
    ],
    FieldType::PALETTE => [
        'tca_out' => [
            'config.type' => 'palette'
        ]
    ],
    FieldType::LINEBREAK => [
        'tca_out' => [
            'config.type' => 'linebreak'
        ]
    ]
];
