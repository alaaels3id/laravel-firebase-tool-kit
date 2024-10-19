<?php

namespace Alaaelsaid\LaravelFirebaseToolKit\Facade;

use Exception;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Psr\Http\Message\StreamInterface;

class FcmTopicHelper
{
    public static function subscribeToTopic(array $tokens, string $topic): StreamInterface|Exception
    {
        if (!isset($topic)) throw new Exception("Topic can't be null");

        if (count($tokens) > 999) throw new Exception("Too much tokens, limit is 999, received " . count($tokens));

        $headers = [
            'Authorization'     => 'Bearer ' . FcmGoogleHelper::getAccessToken(),
            'Content-Type'      => 'application/json',
            'access_token_auth' => 'true',
        ];

        try
        {
            $request = Http::withHeaders($headers)->post("https://iid.googleapis.com/iid/v1:batchAdd", [
                "to"                  => "/topics/" . $topic,
                "registration_tokens" => $tokens
            ]);

            return $request->getBody();
        }
        catch (Exception $e)
        {
            Log::error("[Error] on subscribe to topic: " . $topic, [$e->getMessage()]);

            return $e;
        }
    }

    public static function unSubscribeToTopic(array $tokens, string $topic): StreamInterface|Exception
    {
        $headers = [
            'Authorization'     => 'Bearer ' . FcmGoogleHelper::getAccessToken(),
            'Content-Type'      => 'application/json',
            'access_token_auth' => 'true'
        ];

        $body = ["to" => "/topics/" . $topic, "registration_tokens" => $tokens];

        try
        {
            $request = Http::withHeaders($headers)->post("https://iid.googleapis.com/iid/v1:batchRemove", $body);

            return $request->getBody();
        }
        catch (Exception $e)
        {
            Log::error("[ERROR] unsubscribe to topic: " . $topic, [$e->getMessage()]);

            return $e;
        }
    }

    public static function getTopicsByToken(string $token): Exception|array
    {
        $headers = [
            'Authorization'     => 'Bearer ' . FcmGoogleHelper::getAccessToken(),
            'Content-Type'      => 'application/json',
            'access_token_auth' => 'true'
        ];

        $client = new Client();

        try
        {
            $topics = [];

            $request = $client->get("https://iid.googleapis.com/iid/info/" . $token . '?details=true', ['headers' => $headers]);

            $response = $request->getBody()->getContents();

            foreach (json_decode($response,true)["rel"]["topics"] as $k => $v)
            {
                $topics[] = [$k, $v['addDate']];
            }

            return $topics;
        }
        catch (Exception $e)
        {
            Log::error("[ERROR] get topics by token ", [$e->getMessage()]);

            return $e;
        }
    }
}