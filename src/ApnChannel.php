<?php

namespace BRDevelopers\ApnNotificationChannel;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;
use Pushok\Client;
use Pushok\Notification as PushokNotification;
use Pushok\Payload;
use Pushok\Payload\Alert;
use BRDevelopers\ApnNotificationChannel\Exceptions\CouldNotSendNotification;
use BRDevelopers\ApnNotificationChannel\Exceptions\InvalidPayloadException;

class ApnChannel
{
    /**
     * The Pushok Client factory.
     *
     * @var ClientFactory
     */
    protected $client_factory;

    /**
     * Create an instance of APN channel.
     *
     * @param  ClientFactory  $client_factory
     * @return void
     */
    public function __construct(ClientFactory $client_factory)
    {
        $this->client_factory = $client_factory;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Illuminate\Notifications\Notification  $notification
     * @return void
     *
     * @throws \BRDevelopers\ApnNotificationChannel\Exceptions\InvalidPayloadException
     * @throws \BRDevelopers\ApnNotificationChannel\Exceptions\CouldNotSendNotification
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $deviceTokens = $notifiable->routeNotificationFor('apn', $notification)) {
            return;
        }

        $client = $this->client_factory->instance();

        $client->addNotifications(
            $this->notifications($notification->toApn($notifiable), $deviceTokens)
        );

        $responses = $client->push();

        ApnsResponseCollection::make($responses)
            ->onlyUnsuccessful()
            ->unlessEmpty(function (ApnsResponseCollection $responses) {
                throw CouldNotSendNotification::withUnsuccessful($responses);
            });
    }

    /**
     * Format an array with notifications.
     *
     * @param  \BRDevelopers\ApnNotificationChannel\ApnMessage  $message
     * @param  \Illuminate\Contracts\Support\Arrayable|array  $deviceTokens
     * @return \Pushok\Notification[]
     */
    protected function notifications(ApnMessage $message, $deviceTokens)
    {
        $deviceTokens = $deviceTokens instanceof Arrayable ? $deviceTokens->toArray() : $deviceTokens;

        return collect($deviceTokens)->map(function ($deviceToken) use ($message) {
            return new PushokNotification($this->payload($message), $deviceToken);
        })->all();
    }

    /**
     * Format a payload.
     *
     * @param  \BRDevelopers\ApnNotificationChannel\ApnMessage  $message
     * @return \Pushok\Payload
     *
     * @throws \BRDevelopers\ApnNotificationChannel\Exceptions\InvalidPayloadException
     */
    protected function payload(ApnMessage $message)
    {
        $payload = Payload::create()
            ->setAlert($this->alert($message));

        if ($isContentAvailable = $message->isContentAvailable()) {
            $payload->setContentAvailability($isContentAvailable);
        }

        if ($hasMutableContent = $message->hasMutableContent()) {
            $payload->setMutableContent($hasMutableContent);
        }

        if (! is_null($message->badge)) {
            $payload->setBadge($message->badge);
        }

        if (! is_null($message->sound)) {
            $payload->setSound($message->sound);
        }

        if (! is_null($message->category)) {
            $payload->setCategory($message->category);
        }

        if (! is_null($message->threadId)) {
            $payload->setThreadId($message->threadId);
        }

        try {
            foreach ($message->custom as $key => $value) {
                $payload->setCustomValue($key, $value);
            }
        } catch (\Exception $e) {
            throw new InvalidPayloadException($e->getMessage());
        }

        return $payload;
    }

    /**
     * Format an alert.
     *
     * @param  \BRDevelopers\ApnNotificationChannel\ApnMessage  $message
     * @return \Pushok\Payload\Alert
     */
    protected function alert(ApnMessage $message)
    {
        $alert = Alert::create();

        if ($message->title) {
            $alert->setTitle($message->title);
        }

        if ($message->subtitle) {
            $alert->setSubtitle($message->subtitle);
        }

        if ($message->body) {
            $alert->setBody($message->body);
        }

        if ($message->launchImage) {
            $alert->setLaunchImage($message->launchImage);
        }

        if ($message->titleLocArgs) {
            $alert->setTitleLocArgs($message->titleLocArgs);
        }

        if (! is_null($message->titleLocKey)) {
            $alert->setTitleLocKey($message->titleLocKey);
        }

        if (! is_null($message->actionLocKey)) {
            $alert->setActionLocKey($message->actionLocKey);
        }

        if (! empty($message->locArgs)) {
            $alert->setLocArgs($message->locArgs);
        }

        if ($message->locKey) {
            $alert->setLocKey($message->locKey);
        }

        return $alert;
    }
}
