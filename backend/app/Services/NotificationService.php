<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Role;
use App\Models\User;

class NotificationService
{
    /** Notify every active user holding one of the given role slugs. */
    public function notifyRole(string $roleSlug, string $type, string $title, ?string $body = null, array $data = []): void
    {
        $userIds = User::query()
            ->whereHas('role', fn ($q) => $q->where('slug', $roleSlug))
            ->where('is_active', true)
            ->pluck('id');

        foreach ($userIds as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'data' => $data,
            ]);
        }
    }

    public function notifyUser(User $user, string $type, string $title, ?string $body = null, array $data = []): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);
    }

    /** Storekeepers must be alerted whenever a new invoice needs stock to be prepared. */
    public function notifyStorekeepersOfNewInvoice(string $invoiceNumber, int $invoiceId): void
    {
        $this->notifyRole(
            Role::STOREKEEPER,
            'invoice_needs_fulfillment',
            "طلب تجهيز جديد - فاتورة رقم {$invoiceNumber}",
            'تم حجز الكمية المطلوبة، برجاء تجهيز الصنف وتسليمه.',
            ['sales_invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber],
        );
    }
}
