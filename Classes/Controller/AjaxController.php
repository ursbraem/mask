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

use MASK\Mask\CodeGenerator\HtmlCodeGenerator;
use MASK\Mask\CodeGenerator\SqlCodeGenerator;
use MASK\Mask\CodeGenerator\TcaCodeGenerator;
use MASK\Mask\DataStructure\FieldType;
use MASK\Mask\DataStructure\Tab;
use MASK\Mask\Domain\Repository\StorageRepository;
use MASK\Mask\Domain\Service\SettingsService;
use MASK\Mask\Helper\FieldHelper;
use MASK\Mask\Utility\GeneralUtility as MaskUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
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
     * SqlCodeGenerator
     *
     * @var SqlCodeGenerator
     */
    protected $sqlCodeGenerator;

    /**
     * HtmlCodeGenerator
     *
     * @var HtmlCodeGenerator
     */
    protected $htmlCodeGenerator;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * SettingsService
     *
     * @var SettingsService
     */
    protected $settingsService;

    /**
     * settings
     *
     * @var array
     */
    protected $extSettings;

    /**
     * @var FlashMessageQueue
     */
    protected $flashMessageQueue;

    public function __construct(
        StorageRepository $storageRepository,
        FieldHelper $fieldHelper,
        IconFactory $iconFactory,
        SqlCodeGenerator $sqlCodeGenerator,
        HtmlCodeGenerator $htmlCodeGenerator,
        SettingsService $settingsService
    ) {
        $this->storageRepository = $storageRepository;
        $this->fieldHelper = $fieldHelper;
        $this->iconFactory = $iconFactory;
        $this->sqlCodeGenerator = $sqlCodeGenerator;
        $this->htmlCodeGenerator = $htmlCodeGenerator;
        $this->settingsService = $settingsService;
        $this->flashMessageQueue = new FlashMessageQueue('mask');
        $this->extSettings = $this->settingsService->get();
    }

    public function save(ServerRequestInterface $request): Response
    {
        $params = $request->getParsedBody();
        $isNew = (bool)$params['isNew'];
        $elementKey = $params['element']['key'];
        $fields = json_decode($params['fields'], true);
        $this->storageRepository->update($params['element'], $fields, $params['type'], $isNew);
        $this->generateAction();
        $html = $this->htmlCodeGenerator->generateHtml($elementKey, $params['type']);
        $this->saveHtml($elementKey, $html);
        if ($isNew) {
            $this->addFlashMessage(LocalizationUtility::translate('tx_mask.content.newcontentelement', 'mask'));
        } else {
            $this->addFlashMessage(LocalizationUtility::translate('tx_mask.content.updatedcontentelement', 'mask'));
        }
        return new JsonResponse($this->getFlashMessageQueue()->getAllMessagesAndFlush());
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
        $json['elements'] = $elements;
        return new JsonResponse($json);
    }

    public function loadElement(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $table = $params['type'];
        $elementKey = $params['key'];

        $storage = $this->storageRepository->loadElement($table, $elementKey);
        $json['fields'] = $this->addFields($storage['tca'] ?? [], $table, $elementKey);

        return new JsonResponse($json);
    }

    public function loadField(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $table = $params['type'];
        $key = $params['key'];
        $field = $this->storageRepository->loadField($table, $key);
        $json['field'] = $this->addFields([$key => $field], $table)[0];

        return new JsonResponse($json);
    }

    protected function addFields($fields, $table, $elementKey = '', $parent = null)
    {
        $nestedFields = [];
        foreach ($fields as $key => $field) {
            $newField = [
                'fields' => [],
                'parent' => $parent ?? [],
                'newField' => false,
            ];

            $newField['key'] = $parent ? ($field['coreField'] ? $field['key'] : $field['maskKey']) : $key;

            if ($elementKey !== '') {
                $newField['label'] = $this->getLabel($field, $table, $newField['key'], $elementKey);
                $newField['label'] = $this->translateLabel($newField['label'], $elementKey);
            }

            $fieldType = FieldType::cast($this->getFormType($newField['key'], $table, $elementKey));

            if ($fieldType->isParentField()) {
                $field['inlineFields'] = $this->storageRepository->loadInlineFields($newField['key'], $elementKey);
            }

            // Convert old date format Y-m-d to d-m-Y
            $dbType = $field['config']['dbType'] ?? false;
            if ($dbType && in_array($dbType, ['date', 'datetime'], true)) {
                $format = ($dbType === 'date') ? 'd-m-Y' : 'H:i d-m-Y';
                $lower = $field['config']['range']['lower'] ?? false;
                $upper = $field['config']['range']['upper'] ?? false;
                $pattern = '/^[0-9]{4}]/';
                if ($lower && (bool)preg_match($pattern, $lower)) {
                    $field['config']['range']['lower'] = (new \DateTime($lower))->format($format);
                }
                if ($upper && (bool)preg_match($pattern, $upper)) {
                    $field['config']['range']['upper'] = (new \DateTime($upper))->format($format);
                }
            }

            $newField['isMaskField'] = MaskUtility::isMaskIrreTable($newField['key']);
            $newField['name'] = (string)$fieldType;
            $newField['icon'] = $this->iconFactory->getIcon('mask-fieldtype-' . $newField['name'])->getMarkup();
            $newField['description'] = $field['description'] ?? '';
            $newField['tca'] = $this->convertTcaArrayToFlat($field['config'] ?? []);
            $newField['tca']['l10n_mode'] = $field['l10n_mode'] ?? '';

            if ($fieldType->equals(FieldType::TIMESTAMP)) {
                $format = '';
                switch ($newField['tca']['config.eval']) {
                    case 'date':
                        $format = 'd-m-Y';
                        break;
                    case 'datetime':
                        $format = 'H:i d-m-Y';
                        break;
                    case 'time':
                        $format = 'H:i';
                        break;
                    case 'timesec':
                        $format = 'H:i:s';
                        break;
                }

                $lower = $newField['tca']['config.range.lower'] ?? false;
                if ($lower) {
                    $date = new \DateTime();
                    $date->setTimestamp($lower);
                    $newField['tca']['config.range.lower'] = $date->format($format);
                }
                $upper = $newField['tca']['config.range.upper'] ?? false;
                if ($upper) {
                    $date = new \DateTime();
                    $date->setTimestamp($newField['tca']['config.range.upper']);
                    $newField['tca']['config.range.upper'] = $date->format($format);
                }
            }

            if ($fieldType->equals(FieldType::FILE)) {
                $newField['tca']['imageoverlayPalette'] = $field['imageoverlayPalette'] ?? 1;
                // Since mask v7.0.0 the path for allowedFileExtensions has changed to root level.
                $allowedFileExtensionsPath = 'config.filter.0.parameters.allowedFileExtensions';
                if (isset($newField['tca'][$allowedFileExtensionsPath])) {
                    $newField['tca']['allowedFileExtensions'] = $newField['tca'][$allowedFileExtensionsPath];
                    unset($newField['tca'][$allowedFileExtensionsPath]);
                }
            }

            if ($fieldType->equals(FieldType::CONTENT)) {
                $newField['tca']['cTypes'] = $field['cTypes'] ?? [];
            }

            if ($fieldType->equals(FieldType::INLINE)) {
                $newField['tca']['ctrl.iconfile'] = $field['inlineIcon'] ?? '';
                $newField['tca']['ctrl.label'] = $field['inlineLabel'] ?? '';
            }

            if ($fieldType->isParentField()) {
                $inlineTable = $fieldType->equals(FieldType::INLINE) ? $field['maskKey'] : $table;
                $newField['fields'] = $this->addFields($field['inlineFields'], $inlineTable , $elementKey, $newField);
            }

            $nestedFields[] = $newField;
        }
        return $nestedFields;
    }

    protected function convertTcaArrayToFlat(array $config, $path = ['config']): array
    {
        $tca = [];
        foreach ($config as $key => $value) {
            $path[] = $key;
            if ($key === 'items') {
                $items = $value;
                $itemText = '';
                foreach ($items as $item) {
                    $itemText .= implode(',', $item) . "\n";
                }
                $fullPath = implode('.', $path);
                $tca[$fullPath] = $itemText;
            } elseif (is_array($value)) {
                $tca = array_merge($tca, $this->convertTcaArrayToFlat($value, $path));
            } else {
                if ($key === 'eval') {
                    if ($value !== '') {
                        $keys = explode(',', $value);

                        // Special handling for timestamp field, as the dateType is in the key "config.eval"
                        $dateTypesInKeys = array_intersect($keys, ['date', 'datetime', 'time', 'timesec']);
                        if (count($dateTypesInKeys) > 0) {
                            $fullPath = implode('.', $path);
                            $tca[$fullPath] = $dateTypesInKeys[0];
                            // Remove dateType from normal eval array
                            $keys = array_filter($keys, function ($a) use ($dateTypesInKeys) {
                                return $a !== $dateTypesInKeys[0];
                            });
                        }

                        $evalArray = array_combine($keys, array_fill(0, count($keys), 1));
                        $tca = array_merge($tca, $this->convertTcaArrayToFlat($evalArray, $path));
                    }
                } else {
                    $fullPath = implode('.', $path);
                    $tca[$fullPath] = $value;
                }
            }
            array_pop($path);
        }
        return $tca;
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
                'newField' => true,
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

    public function multiUse(ServerRequestInterface $request): Response
    {
        $params = $request->getQueryParams();
        $key = $params['key'];
        $newField = $params['newField'];
        $elementKey = '';
        if (!$newField) {
            $elementKey = $params['elementKey'];
        }
        $type = $this->fieldHelper->getFieldType($key, $elementKey);
        $multiUseElements = $this->fieldHelper->getStorageRepository()->getElementsWhichUseField($key, $type);
        $json['multiUseElements'] = [];
        foreach ($multiUseElements as $element) {
            $json['multiUseElements'][] = $element['key'];
        }
        return new JsonResponse($json);
    }

    public function icons(ServerRequestInterface $request): Response
    {
        $icons = [
            'Web Application' => ['address-book', 'address-book-o', 'address-card', 'address-card-o', 'adjust', 'anchor', 'archive', 'asterisk', 'at', 'balance-scale', 'ban', 'bank', 'barcode', 'bars', 'bath', 'bathtub', 'battery', 'battery-0', 'battery-1', 'battery-2', 'battery-3', 'battery-4', 'battery-empty', 'battery-full', 'battery-half', 'battery-quarter', 'battery-three-quarters', 'bed', 'beer', 'bell', 'bell-o', 'bell-slash', 'bell-slash-o', 'binoculars', 'birthday-cake', 'bolt', 'bomb', 'book', 'bookmark', 'bookmark-o', 'briefcase', 'bug', 'building', 'building-o', 'bullhorn', 'bullseye', 'calculator', 'calendar', 'calendar-check-o', 'calendar-minus-o', 'calendar-o', 'calendar-plus-o', 'calendar-times-o', 'camera', 'camera-retro', 'cart-arrow-down', 'cart-plus', 'certificate', 'check', 'check-circle', 'check-circle-o', 'child', 'circle-thin', 'clock-o', 'clone', 'close', 'cloud', 'cloud-download', 'cloud-upload', 'code', 'code-fork', 'coffee', 'cogs', 'comment', 'comment-o', 'commenting', 'commenting-o', 'comments', 'comments-o', 'compass', 'copyright', 'creative-commons', 'crop', 'crosshairs', 'cube', 'cubes', 'cutlery', 'dashboard', 'database', 'desktop', 'diamond', 'download', 'drivers-license', 'drivers-license-o', 'edit', 'ellipsis-h', 'ellipsis-v', 'envelope', 'envelope-o', 'envelope-open', 'envelope-open-o', 'envelope-square', 'exclamation', 'exclamation-circle', 'exclamation-triangle', 'external-link', 'external-link-square', 'eye', 'eye-slash', 'eyedropper', 'fax', 'feed', 'female', 'film', 'filter', 'fire', 'fire-extinguisher', 'flag', 'flag-checkered', 'flag-o', 'flash', 'flask', 'folder', 'folder-o', 'folder-open', 'folder-open-o', 'frown-o', 'futbol-o', 'gamepad', 'gavel', 'gears', 'gift', 'glass', 'globe', 'graduation-cap', 'group', 'handshake-o', 'hashtag', 'hdd-o', 'headphones', 'history', 'home', 'hotel', 'hourglass', 'hourglass-1', 'hourglass-2', 'hourglass-3', 'hourglass-end', 'hourglass-half', 'hourglass-o', 'hourglass-start', 'i-cursor', 'id-badge', 'id-card', 'id-card-o', 'image', 'inbox', 'industry', 'info', 'info-circle', 'institution', 'key', 'keyboard-o', 'language', 'laptop', 'leaf', 'legal', 'lemon-o', 'level-down', 'level-up', 'life-bouy', 'life-buoy', 'life-ring', 'life-saver', 'lightbulb-o', 'location-arrow', 'lock', 'magic', 'magnet', 'mail-forward', 'mail-reply', 'mail-reply-all', 'male', 'map', 'map-marker', 'map-o', 'map-pin', 'map-signs', 'meh-o', 'microchip', 'microphone', 'microphone-slash', 'minus', 'minus-circle', 'mobile', 'mobile-phone', 'moon-o', 'mortar-board', 'mouse-pointer', 'music', 'navicon', 'newspaper-o', 'object-group', 'object-ungroup', 'paint-brush', 'paper-plane', 'paper-plane-o', 'paw', 'pencil', 'pencil-square', 'pencil-square-o', 'percent', 'phone', 'phone-square', 'photo', 'picture-o', 'plug', 'plus', 'plus-circle', 'podcast', 'power-off', 'print', 'puzzle-piece', 'qrcode', 'question', 'question-circle', 'quote-left', 'quote-right', 'recycle', 'registered', 'remove', 'reorder', 'reply', 'reply-all', 'retweet', 'road', 'rss', 'rss-square', 's15', 'search', 'search-minus', 'search-plus', 'send', 'send-o', 'server', 'share', 'share-square', 'share-square-o', 'shield', 'shopping-bag', 'shopping-basket', 'shopping-cart', 'shower', 'sign-in', 'sign-out', 'signal', 'sitemap', 'sliders', 'smile-o', 'snowflake-o', 'soccer-ball-o', 'sort', 'sort-alpha-asc', 'sort-alpha-desc', 'sort-amount-asc', 'sort-amount-desc', 'sort-asc', 'sort-desc', 'sort-down', 'sort-numeric-asc', 'sort-numeric-desc', 'sort-up', 'spoon', 'star', 'star-half', 'star-half-empty', 'star-half-full', 'star-half-o', 'star-o', 'sticky-note', 'sticky-note-o', 'street-view', 'suitcase', 'sun-o', 'support', 'tablet', 'tachometer', 'tag', 'tags', 'tasks', 'television', 'terminal', 'thermometer', 'thermometer-0', 'thermometer-1', 'thermometer-2', 'thermometer-3', 'thermometer-4', 'thermometer-empty', 'thermometer-full', 'thermometer-half', 'thermometer-quarter', 'thermometer-three-quarters', 'thumb-tack', 'ticket', 'times', 'times-circle', 'times-circle-o', 'times-rectangle', 'times-rectangle-o', 'tint', 'toggle-off', 'toggle-on', 'trademark', 'trash', 'trash-o', 'tree', 'trophy', 'tv', 'umbrella', 'university', 'unlock', 'unlock-alt', 'unsorted', 'upload', 'user', 'user-circle', 'user-circle-o', 'user-o', 'user-plus', 'user-secret', 'user-times', 'users', 'vcard', 'vcard-o', 'video-camera', 'volume-down', 'volume-off', 'volume-up', 'warning', 'wifi', 'window-close', 'window-close-o', 'window-maximize', 'window-minimize', 'window-restore', 'wrench'],
            'Accessibility' => ['american-sign-language-interpreting', 'asl-interpreting', 'assistive-listening-systems', 'audio-description', 'blind', 'braille', 'cc', 'deaf', 'deafness', 'hard-of-hearing', 'low-vision', 'question-circle-o', 'sign-language', 'signing', 'tty', 'universal-access', 'volume-control-phone', 'wheelchair', 'wheelchair-alt'],
            'Hand' => ['hand-grab-o', 'hand-lizard-o', 'hand-o-down', 'hand-o-left', 'hand-o-right', 'hand-o-up', 'hand-paper-o', 'hand-peace-o', 'hand-pointer-o', 'hand-rock-o', 'hand-scissors-o', 'hand-spock-o', 'hand-stop-o', 'thumbs-down', 'thumbs-o-down', 'thumbs-o-up', 'thumbs-up'],
            'Transportation' => ['automobile', 'bicycle', 'bus', 'cab', 'car', 'fighter-jet', 'motorcycle', 'plane', 'rocket', 'ship', 'space-shuttle', 'subway', 'taxi', 'train', 'truck'],
            'Gender' => ['genderless', 'intersex', 'mars', 'mars-double', 'mars-stroke', 'mars-stroke-h', 'mars-stroke-v', 'mercury', 'neuter', 'transgender', 'transgender-alt', 'venus', 'venus-double', 'venus-mars'],
            'File Type' => ['file', 'file-archive-o', 'file-audio-o', 'file-code-o', 'file-excel-o', 'file-image-o', 'file-movie-o', 'file-o', 'file-pdf-o', 'file-photo-o', 'file-picture-o', 'file-powerpoint-o', 'file-sound-o', 'file-text', 'file-text-o', 'file-video-o', 'file-word-o', 'file-zip-o'],
            'Spinner' => ['circle-o-notch', 'cog', 'gear', 'refresh', 'spinner'],
            'Form Control' => ['check-square', 'check-square-o', 'circle', 'circle-o', 'dot-circle-o', 'minus-square', 'minus-square-o', 'plus-square', 'plus-square-o', 'square', 'square-o'],
            'Payment' => ['cc-amex', 'cc-diners-club', 'cc-discover', 'cc-jcb', 'cc-mastercard', 'cc-paypal', 'cc-stripe', 'cc-visa', 'credit-card', 'credit-card-alt', 'google-wallet'],
            'Chart' => ['area-chart', 'bar-chart', 'bar-chart-o', 'line-chart', 'pie-chart'],
            'Currency' => ['btc', 'cny', 'dollar', 'eur', 'euro', 'gbp', 'gg-circle', 'ils', 'inr', 'jpy', 'krw', 'money', 'rmb', 'rouble', 'rub', 'ruble', 'rupee', 'shekel', 'sheqel', 'try', 'turkish-lira', 'usd', 'viacoin', 'won', 'yen'],
            'Text Editor' => ['align-center', 'align-justify', 'align-left', 'align-right', 'bold', 'chain', 'chain-broken', 'clipboard', 'columns', 'copy', 'cut', 'dedent', 'eraser', 'files-o', 'floppy-o', 'font', 'header', 'indent', 'italic', 'link', 'list', 'list-alt', 'list-ol', 'list-ul', 'outdent', 'paperclip', 'paragraph', 'paste', 'repeat', 'rotate-left', 'rotate-right', 'save', 'scissors', 'strikethrough', 'subscript', 'superscript', 'table', 'text-height', 'text-width', 'th', 'th-large', 'th-list', 'underline', 'undo', 'unlink'],
            'Directional' => ['angle-double-down', 'angle-double-left', 'angle-double-right', 'angle-double-up', 'angle-down', 'angle-left', 'angle-right', 'angle-up', 'arrow-circle-down', 'arrow-circle-left', 'arrow-circle-o-down', 'arrow-circle-o-left', 'arrow-circle-o-right', 'arrow-circle-o-up', 'arrow-circle-right', 'arrow-circle-up', 'arrow-down', 'arrow-left', 'arrow-right', 'arrow-up', 'arrows', 'arrows-h', 'arrows-v', 'caret-down', 'caret-left', 'caret-right', 'caret-square-o-down', 'caret-square-o-left', 'caret-square-o-right', 'caret-square-o-up', 'caret-up', 'chevron-circle-down', 'chevron-circle-left', 'chevron-circle-right', 'chevron-circle-up', 'chevron-down', 'chevron-left', 'chevron-right', 'chevron-up', 'exchange', 'long-arrow-down', 'long-arrow-left', 'long-arrow-right', 'long-arrow-up', 'toggle-down', 'toggle-left', 'toggle-right', 'toggle-up'],
            'Video Player' => ['arrows-alt', 'backward', 'compress', 'eject', 'expand', 'fast-backward', 'fast-forward', 'forward', 'pause', 'pause-circle', 'pause-circle-o', 'play', 'play-circle', 'play-circle-o', 'random', 'step-backward', 'step-forward', 'stop', 'stop-circle', 'stop-circle-o'],
            'Brand' => ['500px', 'adn', 'amazon', 'android', 'angellist', 'apple', 'bandcamp', 'behance', 'behance-square', 'bitbucket', 'bitbucket-square', 'bitcoin', 'black-tie', 'bluetooth', 'bluetooth-b', 'buysellads', 'paypal', 'chrome', 'codepen', 'codiepie', 'connectdevelop', 'contao', 'css3', 'dashcube', 'delicious', 'deviantart', 'digg', 'dribbble', 'dropbox', 'drupal', 'edge', 'eercast', 'empire', 'envira', 'etsy', 'expeditedssl', 'fa', 'facebook', 'facebook-f', 'facebook-official', 'facebook-square', 'firefox', 'first-order', 'flickr', 'font-awesome', 'fonticons', 'fort-awesome', 'forumbee', 'foursquare', 'free-code-camp', 'ge', 'get-pocket', 'gg', 'git', 'git-square', 'github', 'github-alt', 'github-square', 'gitlab', 'gittip', 'glide', 'glide-g', 'google', 'google-plus', 'google-plus-circle', 'google-plus-official', 'google-plus-square', 'gratipay', 'grav', 'hacker-news', 'houzz', 'html5', 'imdb', 'instagram', 'internet-explorer', 'ioxhost', 'joomla', 'jsfiddle', 'lastfm', 'lastfm-square', 'leanpub', 'linkedin', 'linkedin-square', 'linode', 'linux', 'maxcdn', 'meanpath', 'medium', 'meetup', 'mixcloud', 'modx', 'odnoklassniki', 'odnoklassniki-square', 'opencart', 'openid', 'opera', 'optin-monster', 'pagelines', 'pied-piper', 'pied-piper-alt', 'pied-piper-pp', 'pinterest', 'pinterest-p', 'pinterest-square', 'product-hunt', 'qq', 'quora', 'ra', 'ravelry', 'rebel', 'reddit', 'reddit-alien', 'reddit-square', 'renren', 'resistance', 'safari', 'scribd', 'sellsy', 'share-alt', 'share-alt-square', 'shirtsinbulk', 'simplybuilt', 'skyatlas', 'skype', 'slack', 'slideshare', 'snapchat', 'snapchat-ghost', 'snapchat-square', 'soundcloud', 'spotify', 'stack-exchange', 'stack-overflow', 'steam', 'steam-square', 'stumbleupon', 'stumbleupon-circle', 'superpowers', 'telegram', 'tencent-weibo', 'themeisle', 'trello', 'tripadvisor', 'tumblr', 'tumblr-square', 'twitch', 'twitter', 'twitter-square', 'usb', 'viadeo', 'viadeo-square', 'vimeo', 'vimeo-square', 'vine', 'vk', 'wechat', 'weibo', 'weixin', 'whatsapp', 'wikipedia-w', 'windows', 'wordpress', 'wpbeginner', 'wpexplorer', 'wpforms', 'xing', 'xing-square', 'y-combinator', 'y-combinator-square', 'yahoo', 'yc', 'yc-square', 'yelp', 'yoast', 'youtube', 'youtube-play', 'youtube-square'],
            'Medical' => ['ambulance', 'h-square', 'heart', 'heart-o', 'heartbeat', 'hospital-o', 'medkit', 'stethoscope', 'user-md']
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

        // Grouping and parent fields shouldn't be reusable.
        if (FieldType::cast($type)->isGroupingField() || FieldType::cast($type)->isParentField()) {
            $fields = $emptyFields;
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
            if ($field['collision'] ?? false) {
                unset($field['collision']);
                foreach ($field as $type => $typeField) {
                    $tcaFields[$key] = $this->translateTcaFieldLabels($type, $typeField, $tcaFields[$key]);
                }
            } else {
                $tcaFields = $this->translateTcaFieldLabels($key, $field, $tcaFields);
            }
        }
        return new JsonResponse($tcaFields);
    }

    protected function translateTcaFieldLabels($key, $field, $tcaFields)
    {
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
        return $tcaFields;
    }

    public function cTypes(ServerRequestInterface $request): Response
    {
        $items = [];
        $cTypes = $GLOBALS['TCA']['tt_content']['columns']['CType']['config']['items'];
        foreach ($cTypes ?? [] as $type) {
            if ($type[1] !== '--div--') {
                if (GeneralUtility::isFirstPartOfStr($type[0], 'LLL:')) {
                    $items[$type[1]] = LocalizationUtility::translate($type[0], 'mask') . ' (' . $type[1] . ')';
                } else {
                    $items[$type[1]] = $type[0] . ' (' . $type[1] . ')';
                }
            }
        }
        $json['ctypes'] = $items;
        return new JsonResponse($json);
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

        $language['ok'] = LocalizationUtility::translate('tx_mask.ok', 'mask');
        $language['alert'] = LocalizationUtility::translate('tx_mask.alert', 'mask');
        $language['fieldsMissing'] = LocalizationUtility::translate('tx_mask.fieldsMissing', 'mask');

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

    protected function getFormType($fieldKey, $type, $elementKey = '')
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

    /**
     * Generates all the necessary files
     */
    protected function generateAction(): void
    {
        // Set tca to enable DefaultTcaSchema for new inline tables
        $tcaCodeGenerator = GeneralUtility::makeInstance(TcaCodeGenerator::class);
        $tcaCodeGenerator->setInlineTca();

        // Update Database
        $result = $this->sqlCodeGenerator->updateDatabase();
        if (array_key_exists('error', $result)) {
            $this->addFlashMessage($result['error'], '', FlashMessage::ERROR);
        }

        // Clear system cache to force new TCA caching
        $cacheManager = GeneralUtility::makeInstance(CacheManager::class);
        $cacheManager->flushCachesInGroup('system');
    }

    /**
     * Saves Fluid HTML for Contentelements, if File not exists
     *
     * @param string $key
     * @param string $html
     * @return bool
     */
    protected function saveHtml($key, $html): bool
    {
        // fallback to prevent breaking change
        $path = MaskUtility::getTemplatePath($this->extSettings, $key);
        if (file_exists($path)) {
            return false;
        }
        GeneralUtility::writeFile($path, $html);
        return true;
    }

    /**
     * Checks if a key for an element is available
     * @param ServerRequest $request
     * @return Response
     */
    public function checkElementKey(ServerRequest $request): Response
    {
        $elementKey = $request->getQueryParams()['key'];
        $isAvailable = !$this->storageRepository->loadElement('tt_content', $elementKey);

        return new JsonResponse(['isAvailable' => $isAvailable]);
    }

    /**
     * Checks if a key for a field is available
     * @param ServerRequest $request
     * @return Response
     */
    public function checkFieldKey(ServerRequest $request): Response
    {
        $queryParams = $request->getQueryParams();
        $fieldKey = $queryParams['key'];
        $table = $queryParams['table'];
        if (!$table) {
            $table = 'tt_content';
        }
        $type = $queryParams['type'];
        $elementKey = $queryParams['elementKey'];

        $keyExists = false;
        $fieldExists = false;

        if ($type == FieldType::INLINE) {
            $keyExists = array_key_exists($fieldKey, $this->storageRepository->load());
        }

        if ($type == FieldType::CONTENT) {
            $fieldExists = $this->fieldHelper->getFieldType($fieldKey, $elementKey);
        } elseif ($elementKey) {
            $elementsUse = $this->storageRepository->getElementsWhichUseField($fieldKey, $table);
            if (count($elementsUse) > 0) {
                $fieldExists = true;
            }
        } else {
            $fieldExists = $this->storageRepository->loadField($table, $fieldKey);
        }

        return new JsonResponse(['isAvailable' => !$keyExists && !$fieldExists]);
    }

    /**
     * Creates a Message object and adds it to the FlashMessageQueue.
     *
     * @param string $messageBody The message
     * @param string $messageTitle Optional message title
     * @param int $severity Optional severity, must be one of \TYPO3\CMS\Core\Messaging\FlashMessage constants
     * @param bool $storeInSession Optional, defines whether the message should be stored in the session (default) or not
     * @throws \InvalidArgumentException if the message body is no string
     * @see \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    public function addFlashMessage($messageBody, $messageTitle = '', $severity = AbstractMessage::OK, $storeInSession = true)
    {
        if (!is_string($messageBody)) {
            throw new \InvalidArgumentException('The message body must be of type string, "' . gettype($messageBody) . '" given.', 1243258395);
        }
        /* @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            (string)$messageBody,
            (string)$messageTitle,
            $severity,
            $storeInSession
        );
        $this->getFlashMessageQueue()->enqueue($flashMessage);
    }

    protected function getFlashMessageQueue()
    {
        return $this->flashMessageQueue;
    }
}
