<?php

use MASK\Mask\DataStructure\Tab;

return [
    Tab::GENERAL => [
        [
            'config.internal_type' => 6,
            'config.allowed' => 6
        ]
    ],
    Tab::VALIDATION => [
        [
            'config.minitems' => 6,
            'config.maxitems' => 6
        ]
    ],
    Tab::FIELD_CONTROL => [
        [
            'config.fieldControl.editPopup' => 4,
            'config.fieldControl.addRecord' => 4,
            'config.fieldControl.listModule' => 4,
            'config.fieldControl.elementBrowser' => 4,
            'config.fieldControl.insertClipboard' => 4
        ]
    ],

    Tab::WIZARDS => [
        [
            'config.fieldWizard.recordsOverview' => 6,
            'config.fieldWizard.tableList' => 6
        ]
    ],
    Tab::LOCALIZATION => [
        [
            'l10n_mode' => 12
        ],
        [
            'config.behaviour.allowLanguageSynchronization' => 6
        ]
    ],
    Tab::EXTENDED => [
        [
            'config.group.size' => 6,
            'config.autoSizeMax' => 6
        ],
        [
            'config.multiple' => 6
        ]
    ]
];
