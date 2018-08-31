<?php

spl_autoload_register(function ($class) {
    static $classes = null;

    if ($classes === null) {
        $classes = [
            'Commerce\\Commerce'                    => '/src/Commerce.php',
            'Commerce\\SettingsTrait'               => '/src/SettingsTrait.php',
            'Commerce\\CartsManager'                => '/src/CartsManager.php',
            'Commerce\\Interfaces\\Cart'            => '/src/Interfaces/Cart.php',
            'Commerce\\Interfaces\\Payment'         => '/src/Interfaces/Payment.php',
            'Commerce\\Interfaces\\Processor'       => '/src/Interfaces/Processor.php',
            'Commerce\\Carts\\DocListerTrait'       => '/src/Carts/DocListerTrait.php',
            'Commerce\\Carts\\DocListerCart'        => '/src/Carts/DocListerCart.php',
            'Commerce\\Carts\\DocListerOrderCart'   => '/src/Carts/DocListerOrderCart.php',
            'Commerce\\Carts\\SessionCart'          => '/src/Carts/SessionCart.php',
            'Commerce\\Carts\\SimpleCart'           => '/src/Carts/SimpleCart.php',
            'Commerce\\Payments\\Payment'           => '/src/Payments/Payment.php',
            'Commerce\\Payments\\SberbankPayment'   => '/src/Payments/SberbankPayment.php',
            'Commerce\\Processors\\SimpleProcessor' => '/src/Processors/SimpleProcessor.php',

            'FormLister\\Order'     => '/src/Controllers/Order.php',
            'FormLister\\Form'      => '/../../snippets/FormLister/core/controller/Form.php',
            'site_contentDocLister' => '/../../snippets/DocLister/core/controller/site_content.php',
            'DLTemplate'            => '/../../snippets/DocLister/lib/DLTemplate.class.php',
            'Helpers\\Lexicon'      => '/../../snippets/FormLister/lib/Lexicon.php',
            'Helpers\\Mailer'       => '/../../lib/Helpers/Mailer.php',
            'APIhelpers'            => '/../../lib/APIHelpers.class.php',
        ];
    }

    if (isset($classes[$class])) {
        require __DIR__ . $classes[$class];
    }
}, true);
