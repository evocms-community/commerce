<?php

spl_autoload_register(function ($class) {
    static $classes = null;

    if ($classes === null) {
        $classes = [
            'CartDocLister'            => '/src/Controllers/Cart.php',
            'WishlistDocLister'        => '/src/Controllers/Wishlist.php',
            'CustomTableCartDocLister' => '/src/Controllers/CustomTableCart.php',
            'CustomTableLangDocLister' => '/src/Controllers/CustomTableLang.php',
            'CustomLangDocLister'      => '/src/Controllers/CustomLang.php',
            'FormLister\\Order'        => '/src/Controllers/Order.php',
            'FormLister\\Form'         => '/../../snippets/FormLister/core/controller/Form.php',
            'FormLister\\Core'         => '/../../snippets/FormLister/core/FormLister.abstract.php',
            'site_contentDocLister'    => '/../../snippets/DocLister/core/controller/site_content.php',
            'onetableDocLister'        => '/../../snippets/DocLister/core/controller/onetable.php',
            'DocLister'                => '/../../snippets/DocLister/core/DocLister.abstract.php',
            'DLTemplate'               => '/../../snippets/DocLister/lib/DLTemplate.class.php',
            'FormLister\\Validator'    => '/../../snippets/FormLister/lib/Validator.php',
            'Helpers\\Mailer'          => '/../../lib/Helpers/Mailer.php',
            'Helpers\\Config'          => '/../../lib/Helpers/Config.php',
            'Helpers\\Debug'           => '/../../snippets/FormLister/lib/Debug.php',
            'Helpers\\FS'              => '/../../lib/Helpers/FS.php',
            'APIhelpers'               => '/../../lib/APIHelpers.class.php',
            'bLang\bLang'              => '/../../modules/blang/classes/bLang.php',
            'Helpers\\Lexicon'         => [
                '/../../lib/Helpers/Lexicon.php',
                '/../../snippets/DocLister/lib/Lexicon.php',
            ],
        ];
    }

    if (isset($classes[$class])) {
        if (is_array($classes[$class])) {
            foreach ($classes[$class] as $classFile) {
                if (is_readable(__DIR__ . $classFile)) {
                    require __DIR__ . $classFile;
                    return;
                }
            }
        } else {
            require __DIR__ . $classes[$class];
        }

        return;
    }

    if (strpos($class, 'Commerce\\') === 0) {
        $parts = explode('\\', $class);
        array_shift($parts);

        $filename = __DIR__ . '/src/' . implode('/', $parts) . '.php';

        if (is_readable($filename)) {
            require $filename;
        }
    }
}, true);

if (file_exists(__DIR__ . '/../../snippets/FormLister/__autoload.php')) {
    require_once __DIR__ . '/../../snippets/FormLister/__autoload.php';
}

function ci() {
    return Commerce\Container::getInstance();
}
