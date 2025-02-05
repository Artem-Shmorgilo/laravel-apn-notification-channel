<?php

namespace BRDevelopers\ApnNotificationChannel\Exceptions;

use Exception;
use Pushok\ApnsResponseInterface;
use BRDevelopers\ApnNotificationChannel\ApnsResponseCollection;

class CouldNotSendNotification extends Exception
{
    /**
     * @var \BRDevelopers\ApnNotificationChannel\ApnsResponseCollection|\Pushok\ApnsResponseInterface[]
     */
    protected $responses;

    /**
     * Create an instance of exception attaching responses.
     *
     * @param  \BRDevelopers\ApnNotificationChannel\ApnsResponseCollection|\Pushok\ApnsResponseInterface[]  $responses
     * @return $this
     */
    public static function withUnsuccessful(ApnsResponseCollection $responses)
    {
        $message = $responses->map(function (ApnsResponseInterface $response) {
            return $response->getErrorDescription()
                ?? $response->getReasonPhrase()
                ?? $response->getErrorReason();
        })->unique()->implode('; ');

        return (new static($message))
            ->setResponses($responses);
    }

    /**
     * Attach the responses.
     *
     * @param  \BRDevelopers\ApnNotificationChannel\ApnsResponseCollection|\Pushok\ApnsResponseInterface[]  $responses
     * @return $this
     */
    protected function setResponses(ApnsResponseCollection $responses)
    {
        $this->responses = $responses;

        return $this;
    }

    /**
     * Get the responses collection.
     *
     * @return \BRDevelopers\ApnNotificationChannel\ApnsResponseCollection|\Pushok\ApnsResponseInterface[]
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Get a first response.
     *
     * @return \Pushok\ApnsResponseInterface
     * @deprecated Use getResponses() instead.
     */
    public function getResponse()
    {
        return $this->responses[0] ?? null;
    }
}
