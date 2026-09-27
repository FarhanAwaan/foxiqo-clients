<?php

namespace Tests\Unit;

use App\Models\Notification;
use App\Support\MessageCopy;
use Illuminate\Mail\SentMessage;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage as SymfonySentMessage;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

/**
 * Admin → Emails' side panel shows what was actually sent. The copy is read from the mailer's
 * SentMessage, and emails carrying a private sign-in link keep their headers but never their body.
 */
class MessageCopyTest extends TestCase
{
    protected function sent(): SentMessage
    {
        $email = (new Email())
            ->from(new Address('sender@example.com', 'Sender Name'))
            ->to('to@example.com')
            ->cc('cc@example.com')
            ->bcc('bcc@example.com')
            ->replyTo('reply@example.com')
            ->subject('Hello')
            ->html('<p>hi</p>');

        return new SentMessage(new SymfonySentMessage($email, new Envelope(new Address('sender@example.com'), [new Address('to@example.com')])));
    }

    public function test_every_header_and_the_html_are_kept(): void
    {
        $copy = MessageCopy::fromSent($this->sent(), keepBody: true);

        $this->assertSame([['email' => 'sender@example.com', 'name' => 'Sender Name']], $copy['envelope']['from']);
        $this->assertSame([['email' => 'to@example.com']], $copy['envelope']['to']);
        $this->assertSame([['email' => 'cc@example.com']], $copy['envelope']['cc']);
        $this->assertSame([['email' => 'bcc@example.com']], $copy['envelope']['bcc']);
        $this->assertSame([['email' => 'reply@example.com']], $copy['envelope']['reply_to']);
        $this->assertSame('<p>hi</p>', $copy['html_body']);
    }

    public function test_the_body_is_dropped_but_the_envelope_kept_when_told_to(): void
    {
        $copy = MessageCopy::fromSent($this->sent(), keepBody: false);

        $this->assertNull($copy['html_body']);
        $this->assertSame('to@example.com', $copy['envelope']['to'][0]['email']);
    }

    public function test_nothing_is_kept_when_the_mailer_returned_no_sent_message(): void
    {
        $this->assertSame([], MessageCopy::fromSent(null, keepBody: true));
    }

    public function test_private_link_emails_never_keep_their_body(): void
    {
        $this->assertFalse((new Notification(['type' => 'user_invitation']))->keepsContent());
        $this->assertFalse((new Notification(['type' => 'password_reset']))->keepsContent());
        $this->assertTrue((new Notification(['type' => 'deal_paid']))->keepsContent());
    }

    public function test_addresses_fall_back_to_the_recipient_recorded_at_queue_time(): void
    {
        $legacy = new Notification(['type' => 'deal_paid', 'data' => ['recipient_email' => 'old@example.com']]);

        $this->assertSame([['email' => 'old@example.com']], $legacy->addresses('to'));
        $this->assertSame([], $legacy->addresses('from'), 'the sender of a legacy email is unknown, not guessed');

        $stored = new Notification(['data' => ['recipient_email' => 'old@example.com'], 'envelope' => ['to' => [['email' => 'real@example.com']]]]);
        $this->assertSame([['email' => 'real@example.com']], $stored->addresses('to'));
    }
}
