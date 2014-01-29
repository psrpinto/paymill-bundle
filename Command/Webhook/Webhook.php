<?php

namespace Memeoirs\PaymillBundle\Command\Webhook;

class Webhook
{
    public static $events = array(
        'chargeback.executed',
        'transaction.created',
        'transaction.succeeded',
        'transaction.failed',
        'subscription.created',
        'subscription.updated',
        'subscription.deleted',
        'subscription.succeeded',
        'subscription.failed',
        'refund.created',
        'refund.succeeded',
        'refund.failed',
        'payout.transferred',
        'invoice.available',
        'app.merchant.activated',
        'app.merchant.deactivated',
        'app.merchant.rejected',
        'client.updated',
        'app.merchant.app.disabled'
    );

    public static function formatValue($key, $value)
    {
        if ($key === 'event_types' && is_array($value)) {
            return $value === self::$events
                ? 'all'
                : implode(',', $value)
            ;
        }

        return false;
    }
}
