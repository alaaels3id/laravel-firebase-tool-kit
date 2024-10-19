<?php

namespace Alaaelsaid\LaravelFirebaseToolKit\Facade;

use Firebase\JWT\JWT;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait FirebaseNotifications
{
    public function create_http_notification($to): ?object
    {
        $data = array_merge(['type' => (string)$this->type ?? '', 'type_id' => (string)$this->id ?? ''], $this->other);

        return self::send([
            'message' => [
                'token'        => $to,
                'notification' => [
                    'title' => $this->title,
                    'body'  => mb_convert_encoding($this->body, 'UTF-8', 'UTF-8'),
                    'image' => $this->image
                ],
                'data'         => $data,
            ],
        ]);
    }

    private static function send($body): ?object
    {
        try
        {
            return Http::withToken(self::getAccessToken())->post(self::url(), $body)->object();
        }
        catch (Exception $e)
        {
            Log::error($e->getMessage());

            return (object)[];
        }
    }

    private function sendAndSave($model, $save = true): object
    {
        if ($save && method_exists($model,'notifications')) $this->saveOnly($model);

        if (!$response = $this->create_http_notification($this->token)) return $this->fails('something error happened');

        if (empty($response)) return $this->fails('no response');

        if (isset($response->error)) return $this->exception($response);

        return $this->success();
    }

    private static function exception($response): object
    {
        $message = $response->error->message ?? 'unknown';

        self::log($message);

        return self::fails($message);
    }

    private static function fails($message): object
    {
        self::log($message);

        return self::response(false, $message);
    }

    private static function success($message = 'success'): object
    {
        return self::response(true, $message);
    }

    private static function response($status, $message): object
    {
        return (object)compact('status','message');
    }

    private static function url(): string
    {
        return 'https://fcm.googleapis.com/v1/projects/' . self::projectId() . '/messages:send';
    }

    public static function getAccessToken()
    {
        $credentialsPath = config('fcm.fcm_json_file_path');

        $credentials = json_decode(file_get_contents($credentialsPath), true);

        $clientEmail = $credentials['client_email'];

        $now = time();

        $jwt = JWT::encode([
            'iss'   => $clientEmail,
            'sub'   => $clientEmail,
            'aud'   => 'https://oauth2.googleapis.com/token',
            'iat'   => $now,
            'exp'   => $now + 3600,
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        ], $credentials['private_key'], 'RS256');

        $client = new Client();

        $response = $client->post('https://oauth2.googleapis.com/token', [
            'form_params' => [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion'  => $jwt,
            ],
        ]);

        $data = json_decode((string)$response->getBody(), true);

        return $data['access_token'];
    }

    private static function projectId(): string
    {
        return config('fcm.project_id');
    }
}