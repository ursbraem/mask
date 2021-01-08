<?php

use MASK\Mask\DataStructure\FieldType;

return [
    FieldType::STRING => [
        'tca_in' => [
            'config.eval.null' => 0
        ],
        'tca_out' => [
            'config' => [
                'type' => 'input'
            ]
        ],
        'sql' => 'tinytext'
    ],
    FieldType::FLOAT => [
        'tca_in' => [
            'config.eval.null' => 0
        ]
    ],
    FieldType::INTEGER => [
        'tca_in' => [
            'config.eval.null' => 0
        ]
    ],
    FieldType::LINK => [
        'tca_in' => [
            'config.eval.null' => 0
        ]
    ],
    FieldType::DATE => [
        'tca_in' => [
            'config.eval.null' => 0
        ]
    ],
    FieldType::DATETIME => [
        'tca_in' => [
            'config.eval.null' => 0
        ]
    ],
    FieldType::TIMESTAMP => [
        'tca_in' => [
            'config.eval' => 'date',
            'config.eval.null' => 0
        ]
    ],
    FieldType::TEXT => [
        'tca_in' => [
            'config.wrap' => 'virtual',
            'config.format' => '',
            'config.eval.null' => 0
        ]
    ],
    FieldType::RICHTEXT => [
        'tca_out' => [
            'config' => [
                'enableRichtext' => 1
            ]
        ],
        'tca_in' => [
            'config.richtextConfiguration' => ''
        ]
    ],
    FieldType::CHECK => [
        'tca_in' => [
            'config.check.renderType' => ''
        ]
    ],
    FieldType::SELECT => [
        'tca_in' => [
            'config.select.renderType' => 'selectSingle'
        ]
    ],
    FieldType::GROUP => [
        'tca_in' => [
            'config.internal_type' => 'db',
            'config.fieldControl.editPopup' => 1,
            'config.fieldControl.addRecord' => 1,
            'config.fieldControl.listModule' => 1,
        ]
    ],
    FieldType::FILE => [
        'tca_in' => [
            'imageoverlayPalette' => 1,
            'config.appearance.fileUploadAllowed' => 1
        ]
    ],
    FieldType::INLINE => [
        'tca_in' => [
            'config.appearance.levelLinksPosition' => 'top'
        ]
    ],
    FieldType::CONTENT => [
        'tca_in' => [
            'config.appearance.levelLinksPosition' => 'top'
        ]
    ]
];
