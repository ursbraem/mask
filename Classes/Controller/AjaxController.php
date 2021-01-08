<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace MASK\Mask\Controller;

use MASK\Mask\DataStructure\FieldType;
use MASK\Mask\DataStructure\Tab;
use MASK\Mask\Domain\Repository\StorageRepository;
use MASK\Mask\Helper\FieldHelper;
use MASK\Mask\Utility\GeneralUtility as MaskUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

class AjaxController extends ActionController
{
    /**
     * @var FieldHelper
     */
    protected $fieldHelper;

    /**
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    public function __construct(
        StorageRepository $storageRepository,
        FieldHelper $fieldHelper,
        IconFactory $iconFactory
    ) {
        $this->storageRepository = $storageRepository;
        $this->fieldHelper = $fieldHelper;
        $this->iconFactory = $iconFactory;
    }

    public function elements(ServerRequestInterface $request): Response
    {
        $storages = $this->storageRepository->load();
        $elements = [];
        foreach ($storages['tt_content']['elements'] ?? [] as $element) {
            $elements[$element['key']] = [
                'color' => $element['color'],
                'description' => $element['description'],
                'icon' => $element['icon'],
                'key' => $element['key'],
                'label' => $element['label'],
                'shortLabel' => $element['shortLabel'],
            ];
        }
        return new JsonResponse($elements);
    }

    public function loadElement(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $table = $params['type'];
        $elementKey = $params['key'];
        $storage = $this->storageRepository->loadElement($table, $elementKey);
        $storage = $this->storageRepository->prepareStorage($storage, $params['key']);

        $fields = $this->addFields($elementKey, $table, $storage['tca'] ?? []);

        return new JsonResponse($fields);
    }

    protected function addFields($elementKey, $table, $fields, $parent = [])
    {
        $nestedFields = [];
        foreach ($fields as $key => $field) {
            $newField = [
                'fields' => [],
                'parent' => $parent
            ];
            $newField['key'] = is_int($key) ? ($field['maskKey'] ?? $field['key']) : $key;

            if ($field['inPalette'] || !$field['inlineParent']) {
                $formType = $this->getFormType($elementKey, $newField['key'], $table);
            } else {
                $formType = $this->getFormType($elementKey, $newField['key'], $field['inlineParent']);
            }

            $newField['isMaskField'] = MaskUtility::isMaskIrreTable($newField['key']);
            $newField['name'] = $formType;
            $newField['icon'] = $this->iconFactory->getIcon('mask-fieldtype-' . $formType)->getMarkup();
            $newField['label'] = $this->getLabel($field, $table, $newField['key'], $elementKey);
            $newField['label'] = $this->translateLabel($newField['label'], $elementKey);
            $newField['description'] = $field['description'] ?? '';
            $newField['tca'] = $field['config'];
            $newField['tca']['l10n_mode'] = $field['l10n_mode'] ?? '';

            if (FieldType::cast($formType)->isParentField()) {
                $newField['fields'] = $this->addFields($elementKey, $table, $field['inlineFields'], $newField);
            }
            $nestedFields[] = $newField;
        }
        return $nestedFields;
    }

    public function fieldTypes(ServerRequestInterface $request): Response
    {
        $json = [];
        $defaults = require GeneralUtility::getFileAbsFileName('EXT:mask/Configuration/Mask/Defaults.php');
        foreach (FieldType::getConstants() as $type) {
            $config = [
                'name' => $type,
                'icon' => $this->iconFactory->getIcon('mask-fieldtype-' . $type)->getMarkup(),
                'fields' => [],
                'key' => '',
                'label' => '',
                'parent' => [],
                'tca' => [
                    'l10n_mode' => ''
                ]
            ];
            if ($type == FieldType::CONTENT) {
                $config['tca']['cTypes'] = [];
            }
            if (isset($defaults[$type]['tca_in'])) {
                foreach ($defaults[$type]['tca_in'] as $tcaKey => $value) {
                    $config['tca'][$tcaKey] = $value;
                }
            }
            $json[] = $config;
        }
        return new JsonResponse($json);
    }

    public function icons(ServerRequestInterface $request): Response
    {
        $icons = [
            'Web Application' => ['address-book', 'address-book-o', 'address-card', 'address-card-o', 'adjust', 'american-sign-language-interpreting', 'anchor', 'archive', 'area-chart', 'arrows', 'arrows-h', 'arrows-v', 'asl-interpreting', 'assistive-listening-systems', 'asterisk', 'at', 'audio-description', 'automobile', 'balance-scale', 'ban', 'bank', 'bar-chart', 'bar-chart-o', 'barcode', 'bars', 'bath', 'bathtub', 'battery', 'battery-0', 'battery-1', 'battery-2', 'battery-3', 'battery-4', 'battery-empty', 'battery-full', 'battery-half', 'battery-quarter', 'battery-three-quarters', 'bed', 'beer', 'bell', 'bell-o', 'bell-slash', 'bell-slash-o', 'bicycle', 'binoculars', 'birthday-cake', 'blind', 'bluetooth', 'bluetooth-b', 'bolt', 'bomb', 'book', 'bookmark', 'bookmark-o', 'braille', 'briefcase', 'bug', 'building', 'building-o', 'bullhorn', 'bullseye', 'bus', 'cab', 'calculator', 'calendar', 'calendar-check-o', 'calendar-minus-o', 'calendar-o', 'calendar-plus-o', 'calendar-times-o', 'camera', 'camera-retro', 'car', 'caret-square-o-down', 'caret-square-o-left', 'caret-square-o-right', 'caret-square-o-up', 'cart-arrow-down', 'cart-plus', 'cc', 'certificate', 'check', 'check-circle', 'check-circle-o', 'check-square', 'check-square-o', 'child', 'circle', 'circle-o', 'circle-o-notch', 'circle-thin', 'clock-o', 'clone', 'close', 'cloud', 'cloud-download', 'cloud-upload', 'code', 'code-fork', 'coffee', 'cog', 'cogs', 'comment', 'comment-o', 'commenting', 'commenting-o', 'comments', 'comments-o', 'compass', 'copyright', 'creative-commons', 'credit-card', 'credit-card-alt', 'crop', 'crosshairs', 'cube', 'cubes', 'cutlery', 'dashboard', 'database', 'deaf', 'deafness', 'desktop', 'diamond', 'dot-circle-o', 'download', 'drivers-license', 'drivers-license-o', 'edit', 'ellipsis-h', 'ellipsis-v', 'envelope', 'envelope-o', 'envelope-open', 'envelope-open-o', 'envelope-square', 'eraser', 'exchange', 'exclamation', 'exclamation-circle', 'exclamation-triangle', 'external-link', 'external-link-square', 'eye', 'eye-slash', 'eyedropper', 'fax', 'feed', 'female', 'fighter-jet', 'file-archive-o', 'file-audio-o', 'file-code-o', 'file-excel-o', 'file-image-o', 'file-movie-o', 'file-pdf-o', 'file-photo-o', 'file-picture-o', 'file-powerpoint-o', 'file-sound-o', 'file-video-o', 'file-word-o', 'file-zip-o', 'film', 'filter', 'fire', 'fire-extinguisher', 'flag', 'flag-checkered', 'flag-o', 'flash', 'flask', 'folder', 'folder-o', 'folder-open', 'folder-open-o', 'frown-o', 'futbol-o', 'gamepad', 'gavel', 'gear', 'gears', 'gift', 'glass', 'globe', 'graduation-cap', 'group', 'hand-grab-o', 'hand-lizard-o', 'hand-paper-o', 'hand-peace-o', 'hand-pointer-o', 'hand-rock-o', 'hand-scissors-o', 'hand-spock-o', 'hand-stop-o', 'handshake-o', 'hard-of-hearing', 'hashtag', 'hdd-o', 'headphones', 'heart', 'heart-o', 'heartbeat', 'history', 'home', 'hotel', 'hourglass', 'hourglass-1', 'hourglass-2', 'hourglass-3', 'hourglass-end', 'hourglass-half', 'hourglass-o', 'hourglass-start', 'i-cursor', 'id-badge', 'id-card', 'id-card-o', 'image', 'inbox', 'industry', 'info', 'info-circle', 'institution', 'key', 'keyboard-o', 'language', 'laptop', 'leaf', 'legal', 'lemon-o', 'level-down', 'level-up', 'life-bouy', 'life-buoy', 'life-ring', 'life-saver', 'lightbulb-o', 'line-chart', 'location-arrow', 'lock', 'low-vision', 'magic', 'magnet', 'mail-forward', 'mail-reply', 'mail-reply-all', 'male', 'map', 'map-marker', 'map-o', 'map-pin', 'map-signs', 'meh-o', 'microchip', 'microphone', 'microphone-slash', 'minus', 'minus-circle', 'minus-square', 'minus-square-o', 'mobile', 'mobile-phone', 'money', 'moon-o', 'mortar-board', 'motorcycle', 'mouse-pointer', 'music', 'navicon', 'newspaper-o', 'object-group', 'object-ungroup', 'paint-brush', 'paper-plane', 'paper-plane-o', 'paw', 'pencil', 'pencil-square', 'pencil-square-o', 'percent', 'phone', 'phone-square', 'photo', 'picture-o', 'pie-chart', 'plane', 'plug', 'plus', 'plus-circle', 'plus-square', 'plus-square-o', 'podcast', 'power-off', 'print', 'puzzle-piece', 'qrcode', 'question', 'question-circle', 'question-circle-o', 'quote-left', 'quote-right', 'random', 'recycle', 'refresh', 'registered', 'remove', 'reorder', 'reply', 'reply-all', 'retweet', 'road', 'rocket', 'rss', 'rss-square', 's15', 'search', 'search-minus', 'search-plus', 'send', 'send-o', 'server', 'share', 'share-alt', 'share-alt-square', 'share-square', 'share-square-o', 'shield', 'ship', 'shopping-bag', 'shopping-basket', 'shopping-cart', 'shower', 'sign-in', 'sign-language', 'sign-out', 'signal', 'signing', 'sitemap', 'sliders', 'smile-o', 'snowflake-o', 'soccer-ball-o', 'sort', 'sort-alpha-asc', 'sort-alpha-desc', 'sort-amount-asc', 'sort-amount-desc', 'sort-asc', 'sort-desc', 'sort-down', 'sort-numeric-asc', 'sort-numeric-desc', 'sort-up', 'space-shuttle', 'spinner', 'spoon', 'square', 'square-o', 'star', 'star-half', 'star-half-empty', 'star-half-full', 'star-half-o', 'star-o', 'sticky-note', 'sticky-note-o', 'street-view', 'suitcase', 'sun-o', 'support', 'tablet', 'tachometer', 'tag', 'tags', 'tasks', 'taxi', 'television', 'terminal', 'thermometer', 'thermometer-0', 'thermometer-1', 'thermometer-2', 'thermometer-3', 'thermometer-4', 'thermometer-empty', 'thermometer-full', 'thermometer-half', 'thermometer-quarter', 'thermometer-three-quarters', 'thumb-tack', 'thumbs-down', 'thumbs-o-down', 'thumbs-o-up', 'thumbs-up', 'ticket', 'times', 'times-circle', 'times-circle-o', 'times-rectangle', 'times-rectangle-o', 'tint', 'toggle-down', 'toggle-left', 'toggle-off', 'toggle-on', 'toggle-right', 'toggle-up', 'trademark', 'trash', 'trash-o', 'tree', 'trophy', 'truck', 'tty', 'tv', 'umbrella', 'universal-access', 'university', 'unlock', 'unlock-alt', 'unsorted', 'upload', 'user', 'user-circle', 'user-circle-o', 'user-o', 'user-plus', 'user-secret', 'user-times', 'users', 'vcard', 'vcard-o', 'video-camera', 'volume-control-phone', 'volume-down', 'volume-off', 'volume-up', 'warning', 'wheelchair', 'wheelchair-alt', 'wifi', 'window-close', 'window-close-o', 'window-maximize', 'window-minimize', 'window-restore', 'wrench'],
            'Accessibility' => ['american-sign-language-interpreting', 'asl-interpreting', 'assistive-listening-systems', 'audio-description', 'blind', 'braille', 'cc', 'deaf', 'deafness', 'hard-of-hearing', 'low-vision', 'question-circle-o', 'sign-language', 'signing', 'tty', 'universal-access', 'volume-control-phone', 'wheelchair', 'wheelchair-alt'],
            'Hand' => ['hand-grab-o', 'hand-lizard-o', 'hand-o-down', 'hand-o-left', 'hand-o-right', 'hand-o-up', 'hand-paper-o', 'hand-peace-o', 'hand-pointer-o', 'hand-rock-o', 'hand-scissors-o', 'hand-spock-o', 'hand-stop-o', 'thumbs-down', 'thumbs-o-down', 'thumbs-o-up', 'thumbs-up'],
            'Transportation' => ['ambulance', 'automobile', 'bicycle', 'bus', 'cab', 'car', 'fighter-jet', 'motorcycle', 'plane', 'rocket', 'ship', 'space-shuttle', 'subway', 'taxi', 'train', 'truck', 'wheelchair', 'wheelchair-alt'],
            'Gender' => ['genderless', 'intersex', 'mars', 'mars-double', 'mars-stroke', 'mars-stroke-h', 'mars-stroke-v', 'mercury', 'neuter', 'transgender', 'transgender-alt', 'venus', 'venus-double', 'venus-mars'],
            'File Type' => ['file', 'file-archive-o', 'file-audio-o', 'file-code-o', 'file-excel-o', 'file-image-o', 'file-movie-o', 'file-o', 'file-pdf-o', 'file-photo-o', 'file-picture-o', 'file-powerpoint-o', 'file-sound-o', 'file-text', 'file-text-o', 'file-video-o', 'file-word-o', 'file-zip-o'],
            'Spinner' => ['circle-o-notch', 'cog', 'gear', 'refresh', 'spinner'],
            'Form Control' => ['check-square', 'check-square-o', 'circle', 'circle-o', 'dot-circle-o', 'minus-square', 'minus-square-o', 'plus-square', 'plus-square-o', 'square', 'square-o'],
            'Payment' => ['cc-amex', 'cc-diners-club', 'cc-discover', 'cc-jcb', 'cc-mastercard', 'cc-paypal', 'cc-stripe', 'cc-visa', 'credit-card', 'credit-card-alt', 'google-wallet', 'paypal'],
            'Chart' => ['area-chart', 'bar-chart', 'bar-chart-o', 'line-chart', 'pie-chart'],
            'Currency' => ['bitcoin', 'btc', 'cny', 'dollar', 'eur', 'euro', 'gbp', 'gg', 'gg-circle', 'ils', 'inr', 'jpy', 'krw', 'money', 'rmb', 'rouble', 'rub', 'ruble', 'rupee', 'shekel', 'sheqel', 'try', 'turkish-lira', 'usd', 'viacoin', 'won', 'yen'],
            'Text Editor' => ['align-center', 'align-justify', 'align-left', 'align-right', 'bold', 'chain', 'chain-broken', 'clipboard', 'columns', 'copy', 'cut', 'dedent', 'eraser', 'file', 'file-o', 'file-text', 'file-text-o', 'files-o', 'floppy-o', 'font', 'header', 'indent', 'italic', 'link', 'list', 'list-alt', 'list-ol', 'list-ul', 'outdent', 'paperclip', 'paragraph', 'paste', 'repeat', 'rotate-left', 'rotate-right', 'save', 'scissors', 'strikethrough', 'subscript', 'superscript', 'table', 'text-height', 'text-width', 'th', 'th-large', 'th-list', 'underline', 'undo', 'unlink'],
            'Directional' => ['angle-double-down', 'angle-double-left', 'angle-double-right', 'angle-double-up', 'angle-down', 'angle-left', 'angle-right', 'angle-up', 'arrow-circle-down', 'arrow-circle-left', 'arrow-circle-o-down', 'arrow-circle-o-left', 'arrow-circle-o-right', 'arrow-circle-o-up', 'arrow-circle-right', 'arrow-circle-up', 'arrow-down', 'arrow-left', 'arrow-right', 'arrow-up', 'arrows', 'arrows-alt', 'arrows-h', 'arrows-v', 'caret-down', 'caret-left', 'caret-right', 'caret-square-o-down', 'caret-square-o-left', 'caret-square-o-right', 'caret-square-o-up', 'caret-up', 'chevron-circle-down', 'chevron-circle-left', 'chevron-circle-right', 'chevron-circle-up', 'chevron-down', 'chevron-left', 'chevron-right', 'chevron-up', 'exchange', 'hand-o-down', 'hand-o-left', 'hand-o-right', 'hand-o-up', 'long-arrow-down', 'long-arrow-left', 'long-arrow-right', 'long-arrow-up', 'toggle-down', 'toggle-left', 'toggle-right', 'toggle-up'],
            'Video Player' => ['arrows-alt', 'backward', 'compress', 'eject', 'expand', 'fast-backward', 'fast-forward', 'forward', 'pause', 'pause-circle', 'pause-circle-o', 'play', 'play-circle', 'play-circle-o', 'random', 'step-backward', 'step-forward', 'stop', 'stop-circle', 'stop-circle-o', 'youtube-play'],
            'Brand' => ['500px', 'adn', 'amazon', 'android', 'angellist', 'apple', 'bandcamp', 'behance', 'behance-square', 'bitbucket', 'bitbucket-square', 'bitcoin', 'black-tie', 'bluetooth', 'bluetooth-b', 'btc', 'buysellads', 'cc-amex', 'cc-diners-club', 'cc-discover', 'cc-jcb', 'cc-mastercard', 'cc-paypal', 'cc-stripe', 'cc-visa', 'chrome', 'codepen', 'codiepie', 'connectdevelop', 'contao', 'css3', 'dashcube', 'delicious', 'deviantart', 'digg', 'dribbble', 'dropbox', 'drupal', 'edge', 'eercast', 'empire', 'envira', 'etsy', 'expeditedssl', 'fa', 'facebook', 'facebook-f', 'facebook-official', 'facebook-square', 'firefox', 'first-order', 'flickr', 'font-awesome', 'fonticons', 'fort-awesome', 'forumbee', 'foursquare', 'free-code-camp', 'ge', 'get-pocket', 'gg', 'gg-circle', 'git', 'git-square', 'github', 'github-alt', 'github-square', 'gitlab', 'gittip', 'glide', 'glide-g', 'google', 'google-plus', 'google-plus-circle', 'google-plus-official', 'google-plus-square', 'google-wallet', 'gratipay', 'grav', 'hacker-news', 'houzz', 'html5', 'imdb', 'instagram', 'internet-explorer', 'ioxhost', 'joomla', 'jsfiddle', 'lastfm', 'lastfm-square', 'leanpub', 'linkedin', 'linkedin-square', 'linode', 'linux', 'maxcdn', 'meanpath', 'medium', 'meetup', 'mixcloud', 'modx', 'odnoklassniki', 'odnoklassniki-square', 'opencart', 'openid', 'opera', 'optin-monster', 'pagelines', 'paypal', 'pied-piper', 'pied-piper-alt', 'pied-piper-pp', 'pinterest', 'pinterest-p', 'pinterest-square', 'product-hunt', 'qq', 'quora', 'ra', 'ravelry', 'rebel', 'reddit', 'reddit-alien', 'reddit-square', 'renren', 'resistance', 'safari', 'scribd', 'sellsy', 'share-alt', 'share-alt-square', 'shirtsinbulk', 'simplybuilt', 'skyatlas', 'skype', 'slack', 'slideshare', 'snapchat', 'snapchat-ghost', 'snapchat-square', 'soundcloud', 'spotify', 'stack-exchange', 'stack-overflow', 'steam', 'steam-square', 'stumbleupon', 'stumbleupon-circle', 'superpowers', 'telegram', 'tencent-weibo', 'themeisle', 'trello', 'tripadvisor', 'tumblr', 'tumblr-square', 'twitch', 'twitter', 'twitter-square', 'usb', 'viacoin', 'viadeo', 'viadeo-square', 'vimeo', 'vimeo-square', 'vine', 'vk', 'wechat', 'weibo', 'weixin', 'whatsapp', 'wikipedia-w', 'windows', 'wordpress', 'wpbeginner', 'wpexplorer', 'wpforms', 'xing', 'xing-square', 'y-combinator', 'y-combinator-square', 'yahoo', 'yc', 'yc-square', 'yelp', 'yoast', 'youtube', 'youtube-play', 'youtube-square'],
            'Medical' => ['ambulance', 'h-square', 'heart', 'heart-o', 'heartbeat', 'hospital-o', 'medkit', 'plus-square', 'stethoscope', 'user-md', 'wheelchair', 'wheelchair-alt']
        ];
        foreach ($icons as $category => $values) {
            $icons[$category] = array_map(function ($item) {
                return 'fa-' . $item;
            }, $values);
        }
        return new JsonResponse($icons);
    }

    public function existingTca(ServerRequestInterface $request): Response
    {
        $allowedFields = [
            'tt_content' => [
                'header',
                'header_layout',
                'header_position',
                'date',
                'header_link',
                'subheader',
                'bodytext',
                'assets',
                'image',
                'media',
                'imagewidth',
                'imageheight',
                'imageborder',
                'imageorient',
                'imagecols',
                'image_zoom',
                'bullets_type',
                'table_delimiter',
                'table_enclosure',
                'table_caption',
                'file_collections',
                'filelink_sorting',
                'filelink_sorting_direction',
                'target',
                'filelink_size',
                'uploads_description',
                'uploads_type',
                'pages',
                'selected_categories',
                'category_field',
            ]
        ];

        $table = $request->getQueryParams()['table'];
        $type = $request->getQueryParams()['type'];
        $emptyFields = ['mask' => [], 'core' => []];
        $fields = $emptyFields;

        if (in_array($type, [FieldType::PALETTE, FieldType::LINEBREAK])) {
            return new JsonResponse($fields);
        }

        if (empty($GLOBALS['TCA'][$table])) {
            return new JsonResponse($fields);
        }

        if ($type == FieldType::TAB) {
            $fields = $this->fieldHelper->getFieldsByType($type, $table);
            if (empty($fields)) {
                $fields = $emptyFields;
            }
        } elseif (!MaskUtility::isMaskIrreTable($table)) {
            foreach ($GLOBALS['TCA'][$table]['columns'] as $tcaField => $tcaConfig) {
                $isMaskField = MaskUtility::isMaskIrreTable($tcaField);
                if (!$isMaskField && !in_array($tcaField, $allowedFields[$table] ?? [])) {
                    continue;
                }
                // This is needed because the richtext option of bodytext is set via column overrides.
                if ($tcaField === 'bodytext' && $table === 'tt_content') {
                    $fieldType = FieldType::RICHTEXT;
                } else {
                    $fieldType = $this->storageRepository->getFormType($tcaField, '', $table);
                }
                if ($fieldType === $type) {
                    $key = $isMaskField ? 'mask' : 'core';
                    $label = $isMaskField ? (str_replace('tx_mask_', '', $tcaField)) : LocalizationUtility::translate($tcaConfig['label']);
                    $fields[$key][] = [
                        'field' => $tcaField,
                        'label' => $label,
                    ];
                }
            }
        }
        return new JsonResponse($fields);
    }

    public function tcaFields(ServerRequestInterface $request): Response
    {
        $tcaFields = require GeneralUtility::getFileAbsFileName('EXT:mask/Configuration/Mask/TcaFields.php');
        foreach ($tcaFields as $key => $field) {
            $tcaFields[$key]['label'] = LocalizationUtility::translate($field['label'], 'mask');
            if (isset($field['placeholder'])) {
                $tcaFields[$key]['placeholder'] = LocalizationUtility::translate($field['placeholder'], 'mask');
            }
            if (isset($field['description'])) {
                $tcaFields[$key]['description'] = LocalizationUtility::translate($field['description'], 'mask');
            }
            if (isset($tcaFields[$key]['items'])) {
                foreach ($tcaFields[$key]['items'] as $itemKey => $item) {
                    $tcaFields[$key]['items'][$itemKey] = LocalizationUtility::translate($item, 'mask');
                }
            }
        }
        return new JsonResponse($tcaFields);
    }

    public function cTypes(ServerRequestInterface $request): Response
    {
        $items = [];
        $cTypes = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
        if ($cTypes) {
            foreach ($cTypes as $type) {
                if ($type[1] !== '--div--') {
                    if (GeneralUtility::isFirstPartOfStr($type[0], 'LLL:')) {
                        $items[$type[1]] = LocalizationUtility::translate($type[0], 'mask') . ' (' . $type[1] . ')';
                    } else {
                        $items[$type[1]] = $type[0] . ' (' . $type[1] . ')';
                    }
                }
            }
        }
        return new JsonResponse($items);
    }

    public function tabs(ServerRequestInterface $request): Response
    {
        $tabs = [];
        foreach (FieldType::getConstants() as $type) {
            $tabs[$type] = require GeneralUtility::getFileAbsFileName('EXT:mask/Configuration/Mask/Tabs/' . $type . '.php');
        }
        return new JsonResponse($tabs);
    }

    public function language(ServerRequestInterface $request): Response
    {
        $language = [];
        $tabs = [
            Tab::GENERAL => 'tx_mask.tabs.default',
            Tab::APPEARANCE => 'tx_mask.tabs.appearance',
            Tab::DATABASE => 'tx_mask.tabs.database',
            Tab::EXTENDED => 'tx_mask.tabs.extended',
            Tab::FIELD_CONTROL => 'tx_mask.tabs.fieldControl',
            Tab::FILES => 'tx_mask.tabs.files',
            Tab::LOCALIZATION => 'tx_mask.tabs.localization',
            Tab::VALIDATION => 'tx_mask.tabs.validation',
            Tab::WIZARDS => 'tx_mask.tabs.wizards',
        ];

        foreach ($tabs as $key => $tab) {
            $tabs[$key] = LocalizationUtility::translate($tab, 'mask');
        }
        $language['tabs'] = $tabs;
        return new JsonResponse($language);
    }

    public function richtextConfiguration(ServerRequestInterface $request): Response
    {
        $config[''] = LocalizationUtility::translate('tx_mask.config.richtextConfiguration.none', 'mask');
        $presets = array_keys($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets']);
        $presets = array_combine($presets, $presets);
        $config = array_merge($config, $presets);
        return new JsonResponse($config);
    }

    protected function getFormType($elementKey, $fieldKey, $type)
    {
        if ($fieldKey === 'bodytext' && $type === 'tt_content') {
            return FieldType::RICHTEXT;
        }

        return $this->storageRepository->getFormType($fieldKey, $elementKey, $type);
    }

    protected function getLabel($field, $table, $fieldKey, $elementKey)
    {
        // if we have the whole field configuration
        if ($field) {
            // check if this field is in an repeating field
            if (isset($field['inlineParent']) && !is_array($field['inlineParent'])) {
                // if yes, the label is in the configuration
                $label = $field['label'];
            } else {
                // otherwise the type can only be tt_content or pages
                if ($table) {
                    // if we have table param, the type must be the table
                    $type = $table;
                } else {
                    // otherwise try to get the label, set param $excludeInlineFields to true
                    $type = $this->fieldHelper->getFieldType($fieldKey, $elementKey, true);
                }
                $label = $this->fieldHelper->getLabel($elementKey, $fieldKey, $type);
            }
        } else {
            // if we don't have the field configuration, try the best to fetch the type and the correct label
            $type = $this->fieldHelper->getFieldType($fieldKey, $elementKey, false);
            $label = $this->fieldHelper->getLabel($elementKey, $fieldKey, $type);
        }
        return $label;
    }

    protected function translateLabel($key, $element)
    {
        if (is_array($key)) {
            return $key[$element] ?? '';
        }

        if (empty($key) || strpos($key, 'LLL') !== 0) {
            return $key;
        }

        $result = LocalizationUtility::translate($key);
        return empty($result) ? $key : $result;
    }
}
