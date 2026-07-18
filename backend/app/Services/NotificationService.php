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

    /** Accountants must be alerted whenever a new sale invoice request is created. */
    public function notifyAccountantsOfPendingInvoice(string $invoiceNumber, int $invoiceId): void
    {
        $this->notifyRole(
            Role::ACCOUNTANT,
            'invoice_pending_approval',
            "طلب فاتورة جديدة - فاتورة رقم {$invoiceNumber}",
            'هناك طلب فاتورة جديد في انتظار الاعتماد.',
            ['sales_invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber],
        );
    }

    /** Storekeepers must be alerted whenever an approved invoice is ready to fulfill. */
    public function notifyStorekeepersOfApprovedInvoice(string $invoiceNumber, int $invoiceId): void
    {
        $this->notifyRole(
            Role::STOREKEEPER,
            'invoice_ready_fulfillment',
            "فاتورة معتمدة - فاتورة رقم {$invoiceNumber}",
            'فاتورة معتمدة وجاهزة للتجهيز والتسليم.',
            ['sales_invoice_id' => $invoiceId, 'invoice_number' => $invoiceNumber],
        );
    }
}
