<?php

namespace Alaaelsaid\LaravelFirebaseToolKit\Facade;

use App\Facade\Support\Packages\FcmGoogleHelper;
use App\Facade\Support\Packages\FcmTopicHelper;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FirebaseProcess
{
    use FirebaseNotifications;

    public string $title, $body, $click_action;

    public string $type = 'dash', $id = '0', $image = '';

    public string $topic, $token;

    public array $other = [], $tokens = [];

    public static function init(): static
    {
        return new static();
    }

    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    public function setBody($body): static
    {
        $this->body = $body;

        return $this;
    }

    public function setType($type): static
    {
        $this->type = $type;

        return $this;
    }

    public function setId($id): static
    {
        $this->id = $id;

        return $this;
    }

    public function setTopic($topic): static
    {
        $this->topic = $topic;

        return $this;
    }

    public function setOther($other): static
    {
        $this->other = $other;

        return $this;
    }

    public function setIcon($image): static
    {
        $this->image = $image;

        return $this;
    }

    public function setToken($token): static
    {
        $this->token = $token;

        return $this;
    }

    public function setTokens($tokens): static
    {
        $this->tokens = $tokens;

        return $this;
    }

    public function setClickAction($click_action): static
    {
        $this->click_action = $click_action;

        return $this;
    }

    public function combine($model, \Closure $closure = null): object
    {
        try
        {
            if ($web_fcm_token = $model->fcm()?->where('type', 'web')?->latest()?->first()?->fcm)
            {
                $this->setToken($web_fcm_token)->web();
            }

            if ($fcm_token = $model->fcm()?->where('type', 'mobile')?->latest()?->first()?->fcm)
            {
                $this->setToken($fcm_token)->sendOnly($model);
            }

            $this->saveOnly($model, $closure);

            return $this->success();
        }
        catch (Exception $e)
        {
            if (str($e->getMessage())->contains('cURL error 6'))
            {
                return $this->fails('No Internet Connection');
            }
            else
            {
                return $this->fails($e->getMessage());
            }
        }
    }

    public function web(): object
    {
        $response = self::create_http_notification($this->token);

        if (isset($response->error))
        {
            return self::exception($response);
        }

        return self::success();
    }

    public function sendOnly($model): object
    {
        return self::sendAndSave($model,false);
    }

    public function saveOnly($model, \Closure $closure = null)
    {
        if(!method_exists($model,'notifications') && $closure == null) return null;

        if($closure) return $closure($model);

        return $model->notifications()->create([
            'title'   => mb_convert_encoding($this->title, 'UTF-8', 'UTF-8'),
            'body'    => mb_convert_encoding($this->body, 'UTF-8', 'UTF-8'),
            'type'    => $this->type,
            'type_id' => $this->id,
        ]);
    }

    public function sendToGroup(): object
    {
        return $this->setTokens($this->tokens)->setTopic($this->topic)->subscribe()->byTopic();
    }

    public function byTopic(): object
    {
        $data = [];

        $headers = ['Authorization' => 'Bearer ' . FcmGoogleHelper::getAccessToken(), 'Content-Type'  => 'application/json'];

        try
        {
            $data['message'] = [];
            $data['message']['webpush']['notification']['title'] = $this->title;
            $data['message']['webpush']['notification']['body'] = $this->body;
            $data['message']['webpush']['notification']['icon'] = ($this->image != null ? asset_url($this->image) : '');
            $data['message']['webpush']['notification']['click_action'] = $this->click_action ?? '';

            $data['message']['topic'] = $this->topic;

            $url = 'https://fcm.googleapis.com/v1/projects/' . config('fcm.project_id') . '/messages:send';

            $request = Http::withHeaders($headers)->post($url, $data);

            return $request->getBody();
        }
        catch (Exception $e)
        {
            Log::error("[Notification] ERROR", [$e->getMessage()]);

            return $e;
        }
    }

    public function sendToGroupTransAr(): ?object
    {
        $this->title = google_translate()->trans($this->title,'ar','ar');

        $this->body = google_translate()->trans($this->body,'ar','ar');

        return $this->setTokens($this->tokens)->setTopic($this->topic)->subscribe()->byTopic();
    }

    public function sendToGroupTransEn(): ?object
    {
        $this->title = google_translate()->trans($this->title,'en','ar');

        $this->body = google_translate()->trans($this->body,'en','ar');

        return $this->setTokens($this->tokens)->setTopic($this->topic)->subscribe()->byTopic();
    }

    public function subscribe(): static
    {
        FcmTopicHelper::subscribeToTopic(Arr::wrap($this->token), $this->topic);

        return $this;
    }

    public function unsubscribe(): static
    {
        FcmTopicHelper::unSubscribeToTopic(Arr::wrap($this->token), $this->topic);

        return $this;
    }

    public static function log($message): void
    {
        Log::warning(now()->format('H:i') . ' : ' . $message);
    }
}