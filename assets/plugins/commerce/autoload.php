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
            'Commerce\\Carts\\DocListerItemsList'   => '/src/Carts/DocListerItemsList.php',
            'Commerce\\Carts\\SessionCart'          => '/src/Carts/SessionCart.php',
            'Commerce\\Carts\\SimpleCart'           => '/src/Carts/SimpleCart.php',
            'Commerce\\Payments\\Payment'           => '/src/Payments/Payment.php',
            'Commerce\\Processors\\SimpleProcessor' => '/src/Processors/SimpleProcessor.php',

            'Commerce\\Payments\\SberbankPayment'    => '/src/Payments/SberbankPayment.php',
            'Commerce\\Payments\\YandexkassaPayment' => '/src/Payments/YandexkassaPayment.php',

            'FormLister\\Order'     => '/src/Controllers/Order.php',
            'FormLister\\Form'      => '/../../snippets/FormLister/core/controller/Form.php',
            'FormLister\\Core'      => '/../../snippets/FormLister/core/FormLister.abstract.php',
            'site_contentDocLister' => '/../../snippets/DocLister/core/controller/site_content.php',
            'DLTemplate'            => '/../../snippets/DocLister/lib/DLTemplate.class.php',
            'Helpers\\Lexicon'      => '/../../snippets/FormLister/lib/Lexicon.php',
            'FormLister\\Validator' => '/../../snippets/FormLister/lib/Validator.php',
            'Helpers\\Mailer'       => '/../../lib/Helpers/Mailer.php',
            'Helpers\\Config'       => '/../../lib/Helpers/Config.php',
            'APIhelpers'            => '/../../lib/APIHelpers.class.php',
        ];
    }

    if (isset($classes[$class])) {
        require __DIR__ . $classes[$class];
    }
}, true);
