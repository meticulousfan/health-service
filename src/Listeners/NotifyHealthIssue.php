<?php

namespace PragmaRX\Health\Listeners;

use Illuminate\Support\Facades\Notification;
use PragmaRX\Health\Events\RaiseHealthIssue;
use PragmaRX\Health\Notifications\HealthStatus;
use ReflectionClass;
use ReflectionException;

class NotifyHealthIssue
{
    /**
     * @return static
     */
    private function getNotifiableUsers()
    {
        return collect(config('health.notifications.users.emails'))->map(
            function ($item) {
                $model = instantiate(
                    config('health.notifications.users.model')
                );

                $model->email = $item;

                return $model;
            }
        );
    }

    /**
     * Handle the event.
     *
     * @param  RaiseHealthIssue  $event
     * @return void
     *
     * @throws ReflectionException
     */
    public function handle(RaiseHealthIssue $event)
    {
        $notifier = config('health.notifications.notifier');
        if ($notifier) {
            $notifierClass = new ReflectionClass($notifier);
        } else {
            $notifierClass = HealthStatus::class;
        }
        try {
            $event->failure->targets->each(function ($target) use ($event, $notifierClass) {
                if (! $target->result->healthy) {
                    Notification::send(
                        $this->getNotifiableUsers(),
                        $notifierClass->newInstance($target, $event->channel)
                    );
                }
            });
        } catch (\Exception $exception) {
            report($exception);
        } catch (\Throwable $error) {
            report($error);
        }
    }
}
