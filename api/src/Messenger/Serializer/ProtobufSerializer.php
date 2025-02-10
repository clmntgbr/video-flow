<?php

namespace App\Messenger\Serializer;

use Google\Protobuf\Internal\Message;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

class ProtobufSerializer implements SerializerInterface
{
    public function decode(array $encodedEnvelope): Envelope
    {
        $body = $encodedEnvelope['body'] ?? [];
        $headers = $encodedEnvelope['headers'];

        $messageClass = $headers['type'] ?? null;
        if (null === $messageClass) {
            throw new MessageDecodingFailedException('Message type header is required');
        }

        if (!class_exists($messageClass)) {
            throw new MessageDecodingFailedException(sprintf('Message class "%s" not found', $messageClass));
        }

        $body = json_decode($body, true);

        if (null === $body && !$body['args'] && $body['args'][0]) {
            throw new MessageDecodingFailedException('Invalid JSON');
        }

        /** @var Message $message */
        $message = new $messageClass();
        $message->mergeFromJsonString($body['args'][0]);

        return new Envelope($message);
    }

    public function encode(Envelope $envelope): array
    {
        $message = $envelope->getMessage();

        if (!$message instanceof Message) {
            throw new \InvalidArgumentException(sprintf('Message must be an instance of %s, %s given', Message::class, get_class($message)));
        }

        return [
            'body' => json_encode([
                'task' => 'tasks.process_message',
                'args' => [$message->serializeToJsonString()],
                'queue' => $this->convertClassNameToSnakeCase(get_class($message)),
            ]),
            'headers' => [
                'type' => get_class($message),
                'Content-Type' => 'application/json',
            ],
        ];
    }

    private function convertClassNameToSnakeCase(string $className): string
    {
        $className = substr(strrchr($className, '\\'), 1) ?: $className;

        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $className));
    }
}
