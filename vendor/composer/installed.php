<?php return array(
    'root' => array(
        'name' => 'wirecatllc/stripe_universal',
        'pretty_version' => 'dev-master',
        'version' => 'dev-master',
        'reference' => '1a8eda65351dbd20d0190e8bf582189a4a98107b',
        'type' => 'blesta-gateway-nonmerchant',
        'install_path' => __DIR__ . '/../../',
        'aliases' => array(),
        'dev' => true,
    ),
    'versions' => array(
        'blesta/composer-installer' => array(
            'pretty_version' => '1.1.0',
            'version' => '1.1.0.0',
            'reference' => 'f0529f892e4cb0db5e9e949031965f212c7da269',
            'type' => 'composer-installer',
            'install_path' => __DIR__ . '/../blesta/composer-installer',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'composer/installers' => array(
            'pretty_version' => 'v1.12.0',
            'version' => '1.12.0.0',
            'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
            'type' => 'composer-plugin',
            'install_path' => __DIR__ . '/./installers',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'roundcube/plugin-installer' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'shama/baton' => array(
            'dev_requirement' => false,
            'replaced' => array(
                0 => '*',
            ),
        ),
        'stripe/stripe-php' => array(
            'pretty_version' => 'v7.128.0',
            'version' => '7.128.0.0',
            'reference' => 'c704949c49b72985c76cc61063aa26fefbd2724e',
            'type' => 'library',
            'install_path' => __DIR__ . '/../stripe/stripe-php',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
        'wirecatllc/stripe_universal' => array(
            'pretty_version' => 'dev-master',
            'version' => 'dev-master',
            'reference' => '1a8eda65351dbd20d0190e8bf582189a4a98107b',
            'type' => 'blesta-gateway-nonmerchant',
            'install_path' => __DIR__ . '/../../',
            'aliases' => array(),
            'dev_requirement' => false,
        ),
    ),
);
