<?php
/**
 * @link http://htmlpurifier.org/live/configdoc/plain.html
 */

return [
    'encoding'         => 'UTF-8',
    'finalize'         => true,
    'ignoreNonStrings' => false,
    'cachePath'        => storage_path('app/purifier'),
    'cacheFileMode'    => 0755,

    'settings' => [
        /**
         * ✅ TЗ профіль: allowlist тільки:
         * <a href="" title=""></a>, <code></code>, <i></i>, <strong></strong>
         * Все інше вирізається/санітизується.
         * XHTML doctype + блок unsafe-схем.
         */
        'default' => [
            // Вимога "валидный XHTML"
            'HTML.Doctype' => 'XHTML 1.0 Transitional',

            // Дозволені теги/атрибути
            'HTML.Allowed' => 'a[href|title],code,i,strong',

            // Забороняємо будь-які стилі (щоб не було style-based XSS)
            'CSS.AllowedProperties' => [],

            // Дозволені URI схеми (важливо: блокує javascript:)
            'URI.AllowedSchemes' => [
                'http' => true,
                'https' => true,
                'mailto' => true,
            ],

            // Ніяких автопараграфів (бо змінює розмітку непередбачувано)
            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => true,
        ],

        // Можеш лишити як є — ми їх не використовуємо
        'test' => [
            'Attr.EnableID' => 'true',
        ],

        // Лишаємо, але НЕ використовуємо для коментарів
        'youtube' => [
            'HTML.SafeIframe'      => 'true',
            'URI.SafeIframeRegexp' => "%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%"
        ],

        // Лишаємо як є (не використовується в comments)
        'custom_definition' => [
            'id'  => 'html5-definitions',
            'rev' => 1,
            'debug' => false,
            'elements' => [
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],

                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],

                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],

                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                    'width' => 'Length',
                    'height' => 'Length',
                    'poster' => 'URI',
                    'preload' => 'Enum#auto,metadata,none',
                    'controls' => 'Bool',
                ]],
                ['source', 'Block', 'Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                ]],

                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty', 'Core'],

                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],
            'attributes' => [
                ['iframe', 'allowfullscreen', 'Bool'],
                ['table', 'height', 'Text'],
                ['td', 'border', 'Text'],
                ['th', 'border', 'Text'],
                ['tr', 'width', 'Text'],
                ['tr', 'height', 'Text'],
                ['tr', 'border', 'Text'],
            ],
        ],

        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
        ],

        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
        ],
    ],
];
