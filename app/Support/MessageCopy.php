<?php

namespace App\Support;

use Illuminate\Mail\SentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

/**
 * What Admin → Emails keeps of a message: who it was from/to/cc/bcc and the HTML that was
 * delivered. Read from the SentMessage the mailer returns — i.e. exactly what left the
 * server, headers included — rather than rebuilt later from data that may have changed.
 */
class MessageCopy
{
    /**
     * @return array{envelope: array<string, array>, html_body: ?string}|array{}  empty when there is nothing to keep
     */
    public static function fromSent(?SentMessage $sent, bool $keepBody): array
    {
        $message = $sent?->getOriginalMessage();

        if (!$message instanceof Email) {
            return [];
        }

        $html = $message->getHtmlBody();

        return [
            'envelope' => [
                'from' => self::addresses($message->getFrom()),
                'to' => self::addresses($message->getTo()),
                'cc' => self::addresses($message->getCc()),
                'bcc' => self::addresses($message->getBcc()),
                'reply_to' => self::addresses($message->getReplyTo()),
            ],
            'html_body' => $keepBody && is_string($html) ? $html : null,
        ];
    }

    /**
     * @param array<int, Address> $list
     * @return array<int, array{email: string, name?: string}>
     */
    protected static function addresses(array $list): array
    {
        return array_values(array_map(
            fn (Address $a) => array_filter(['email' => $a->getAddress(), 'name' => $a->getName() ?: null]),
            $list
        ));
    }
}
