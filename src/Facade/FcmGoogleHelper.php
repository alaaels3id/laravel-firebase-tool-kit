<?php

namespace Alaaelsaid\LaravelFirebaseToolKit\Facade;

use Google\Client as GoogleClient;
use Google\Exception;
use Google\Service\FirebaseCloudMessaging;

class FcmGoogleHelper
{
    public static function getAccessToken()
    {
        try
        {
            $client = new GoogleClient();

            $client->setAuthConfig(config_path('fcm.json'));

            $client->addScope(FirebaseCloudMessaging::CLOUD_PLATFORM);

            $accessToken = self::generateToken($client);

            $client->setAccessToken($accessToken);

            return $accessToken["access_token"];
        }
        catch (Google_Exception|Exception $e)
        {
            return $e;
        }
    }

    private static function generateToken($client): array
    {
        $client->fetchAccessTokenWithAssertion();

        return $client->getAccessToken();
    }
}