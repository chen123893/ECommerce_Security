<?php
/**
 * Settings for the security audit log and suspicious activity rules.
 *
 * Change a threshold, window or the enabled flag here to tune detection.
 * group_by accepts identifier (the hashed email), ip, or actor_or_ip.
 */
function security_audit_settings(): array
{
    return [
        // Audit events and acknowledged alerts are deleted after this many days.
        'retention_days' => 90,
        // Rows shown per page on the administrator dashboard.
        'page_size' => 25,
        'rules' => [
            'repeated_failed_login_account' => [
                'enabled'        => true,
                'label'          => 'Repeated failed logins on one account',
                'events'         => ['login_failure', 'login_blocked', 'mfa_failure', 'mfa_blocked'],
                'group_by'       => 'identifier',
                'threshold'      => 5,
                'window_minutes' => 15,
                'severity'       => 'high',
                'false_positive' => 'A customer who has forgotten their password can reach this count.',
            ],
            'repeated_failed_login_ip' => [
                'enabled'        => true,
                'label'          => 'Repeated failed logins from one network address',
                'events'         => ['login_failure', 'login_blocked', 'mfa_failure', 'mfa_blocked'],
                'group_by'       => 'ip',
                'threshold'      => 10,
                'window_minutes' => 15,
                'severity'       => 'high',
                'false_positive' => 'Several people behind one shared address, such as a campus network.',
            ],
            'repeated_access_denied' => [
                'enabled'        => true,
                'label'          => 'Repeated requests for pages the user may not open',
                'events'         => ['access_denied', 'csrf_rejected'],
                'group_by'       => 'actor_or_ip',
                'threshold'      => 5,
                'window_minutes' => 10,
                'severity'       => 'medium',
                'false_positive' => 'Old bookmarks, or a session that expired while several admin tabs were open.',
            ],
            'repeated_admin_deletions' => [
                'enabled'        => true,
                'label'          => 'Unusual number of administrative deletions',
                'events'         => ['admin_product_deleted', 'admin_user_deleted', 'admin_order_deleted', 'admin_client_deleted'],
                'group_by'       => 'actor_or_ip',
                'threshold'      => 10,
                'window_minutes' => 5,
                'severity'       => 'medium',
                'false_positive' => 'A planned catalogue clean-up by an administrator.',
            ],
        ],
    ];
}
