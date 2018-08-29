<?php

spl_autoload_register(function ($class) {
    static $classes = null;

    if ($classes === null) {
        $classes = [
            'Commerce\\Commerce'            => '/src/Commerce.php',
            'Commerce\\DefaultCart'         => '/src/DefaultCart.php',
            'Commerce\\Interfaces\\Cart'    => '/src/Interfaces/Cart.php',

            'FormLister\\OrderController'   => '/src/Controllers/OrderController.php',
            'FormLister\\Form'              => '/../../snippets/FormLister/core/controller/Form.php',
            'DLTemplate'                    => '/../../snippets/DocLister/lib/DLTemplate.class.php',
            'Helpers\\Lexicon'              => '/../../snippets/FormLister/lib/Lexicon.php',
            'Helpers\\Mailer'               => '/../../lib/Helpers/Mailer.php',
        ];
    }

    if (isset($classes[$class])) {
        require __DIR__ . $classes[$class];
    }
}, true);
