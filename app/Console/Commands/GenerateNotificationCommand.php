<?php
namespace App\Console\Commands;
use App\Services\NotificationService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
#[Signature('notification:generate')]
#[Description('Generate employee notifications')]
class GenerateNotificationCommand extends Command
{
    public function handle(NotificationService $service)
    {
        $service->generateNotifications();
        $this->info('Notification generated successfully.');
        return self::SUCCESS;
    }
}
