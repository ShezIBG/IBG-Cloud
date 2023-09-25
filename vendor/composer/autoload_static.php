<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitba5c05c49dad0e1f5194a9110dd0d198
{
    public static $files = array (
        'c964ee0ededf28c96ebd9db5099ef910' => __DIR__ . '/..' . '/guzzlehttp/promises/src/functions_include.php',
        'a0edc8309cc5e1d60e3047b5df6b7052' => __DIR__ . '/..' . '/guzzlehttp/psr7/src/functions_include.php',
        '37a3dc5111fe8f707ab4c132ef1dbc62' => __DIR__ . '/..' . '/guzzlehttp/guzzle/src/functions_include.php',
    );

    public static $prefixLengthsPsr4 = array (
        'm' => 
        array (
            'mikehaertl\\wkhtmlto\\' => 20,
            'mikehaertl\\tmp\\' => 15,
            'mikehaertl\\shellcommand\\' => 24,
        ),
        'S' => 
        array (
            'Symfony\\Component\\EventDispatcher\\' => 34,
            'Stripe\\' => 7,
        ),
        'P' => 
        array (
            'Psr\\Http\\Message\\' => 17,
            'Picqer\\Barcode\\' => 15,
        ),
        'O' => 
        array (
            'Ockle\\GoCardlessWebhook\\' => 24,
        ),
        'G' => 
        array (
            'GuzzleHttp\\Psr7\\' => 16,
            'GuzzleHttp\\Promise\\' => 19,
            'GuzzleHttp\\' => 11,
            'GoCardlessPro\\' => 14,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'mikehaertl\\wkhtmlto\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/phpwkhtmltopdf/src',
        ),
        'mikehaertl\\tmp\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-tmpfile/src',
        ),
        'mikehaertl\\shellcommand\\' => 
        array (
            0 => __DIR__ . '/..' . '/mikehaertl/php-shellcommand/src',
        ),
        'Symfony\\Component\\EventDispatcher\\' => 
        array (
            0 => __DIR__ . '/..' . '/symfony/event-dispatcher',
        ),
        'Stripe\\' => 
        array (
            0 => __DIR__ . '/..' . '/stripe/stripe-php/lib',
        ),
        'Psr\\Http\\Message\\' => 
        array (
            0 => __DIR__ . '/..' . '/psr/http-message/src',
        ),
        'Picqer\\Barcode\\' => 
        array (
            0 => __DIR__ . '/..' . '/picqer/php-barcode-generator/src',
        ),
        'Ockle\\GoCardlessWebhook\\' => 
        array (
            0 => __DIR__ . '/..' . '/ockle/gocardless-webhook/src',
        ),
        'GuzzleHttp\\Psr7\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/psr7/src',
        ),
        'GuzzleHttp\\Promise\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/promises/src',
        ),
        'GuzzleHttp\\' => 
        array (
            0 => __DIR__ . '/..' . '/guzzlehttp/guzzle/src',
        ),
        'GoCardlessPro\\' => 
        array (
            0 => __DIR__ . '/..' . '/gocardless/gocardless-pro/lib',
        ),
    );

    public static $prefixesPsr0 = array (
        'O' => 
        array (
            'OAuth2' => 
            array (
                0 => __DIR__ . '/..' . '/adoy/oauth2/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitba5c05c49dad0e1f5194a9110dd0d198::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitba5c05c49dad0e1f5194a9110dd0d198::$prefixDirsPsr4;
            $loader->prefixesPsr0 = ComposerStaticInitba5c05c49dad0e1f5194a9110dd0d198::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
