<?php

namespace App\Messenger\Serializer;

use Google\Protobuf\Internal\Message;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Stamp\SerializerStamp;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class ProtobufSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'];
        $headers = $encodedEnvelope['headers'];

        $messageClass = $headers['type'] ?? null;
        if (null === $messageClass) {
            throw new MessageDecodingFailedException('Message type header is required');
        }

        if (!class_exists($messageClass)) {
            throw new MessageDecodingFailedException(sprintf('Message class "%s" not found', $messageClass));
        }

        /** @var Message $message */
        $message = new $messageClass;
        $message->mergeFromJsonString(base64_decode($body));

        return new Envelope($message);
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();
        
        if (!$message instanceof Message) {
            throw new \InvalidArgumentException(sprintf(
                'Message must be an instance of %s, %s given',
                Message::class,
                get_class($message)
            ));
        }

        $stamps = $envelope->all();

        return [
            'body' => $message->serializeToJsonString(),
            'headers' => [
                'type' => get_class($message),
                'stamps' => serialize($stamps),
                'Content-Type' => 'application/x-protobuf'
            ],
        ];
    }
}