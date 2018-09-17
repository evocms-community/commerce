<?php

spl_autoload_register(function ($class) {
    static $classes = null;

    if ($classes === null) {
        $classes = [
            'FormLister\\Order'     => '/src/Controllers/Order.php',
            'FormLister\\Form'      => '/../../snippets/FormLister/core/controller/Form.php',
            'FormLister\\Core'      => '/../../snippets/FormLister/core/FormLister.abstract.php',
            'site_contentDocLister' => '/../../snippets/DocLister/core/controller/site_content.php',
            'DLTemplate'            => '/../../snippets/DocLister/lib/DLTemplate.class.php',
            'Helpers\\Lexicon'      => '/../../snippets/FormLister/lib/Lexicon.php',
            'FormLister\\Validator' => '/../../snippets/FormLister/lib/Validator.php',
            'Helpers\\Mailer'       => '/../../lib/Helpers/Mailer.php',
            'Helpers\\Config'       => '/../../lib/Helpers/Config.php',
            'Helpers\\Debug'        => '/../../snippets/FormLister/lib/Debug.php',
            'APIhelpers'            => '/../../lib/APIHelpers.class.php',
        ];
    }

    if (isset($classes[$class])) {
        require __DIR__ . $classes[$class];
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
