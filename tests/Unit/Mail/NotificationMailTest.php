<?php

namespace Tests\Unit\Mail;

use App\Mail\NotificationMail;
use App\Models\Notification;
use Tests\TestCase;

class NotificationMailTest extends TestCase
{
    public function test_mail_builds_the_notification_view_with_its_subject(): void
    {
        $notification = new Notification([
            'judul' => 'Pengingat KGB',
        ]);

        $mail = (new NotificationMail($notification))->build();

        $this->assertSame('Pengingat KGB', $mail->subject);
        $this->assertSame('notification.email', $mail->view);
    }

    public function test_mail_defines_its_envelope_content_and_empty_attachments(): void
    {
        $notification = new Notification([
            'judul' => 'Pemberitahuan Kenaikan Gaji',
        ]);
        $mail = new NotificationMail($notification);

        $this->assertSame('Pemberitahuan Kenaikan Gaji', $mail->envelope()->subject);
        $this->assertSame('notification.email', $mail->content()->view);
        $this->assertSame([], $mail->attachments());
    }
}
