<?php
/**
 * @see https://github.com/Edujugon/PushNotification
 */

return [
    'gcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => 'My_ApiKey',
    ],
    'fcm' => [
        'priority' => 'normal',
        'dry_run' => false,
        'apiKey' => env('NOTIFICATION_SERVER_KEY'),
    ],
    'apn' => [
        'certificate' => __DIR__ . '/iosCertificates/' .env('APN_NOTIFICATION_CERTIFICATE'),//old - demo_development.pem
        'passPhrase' => env('APN_NOTIFICATION_CERTIFICATE_PASSWORD'), //Optional
        'passFile' => __DIR__ . '/iosCertificates/'.env('APN_NOTIFICATION_CERTIFICATE'), //Optional
        'dry_run' => env('APN_NOTIFICATION_ENV'),
    ],
];
