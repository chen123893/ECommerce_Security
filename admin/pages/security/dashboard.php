<?php
include '../../includes/auth.php';
require_admin_role();
require_once '../../../includes/csrf.php';

$pageTitle = "Security Monitor | ShopEase Admin";
$pageHeading = "Security Monitor";

$db = security_audit_db();
$settings = security_audit_settings();
$catalog = security_event_catalog();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_token_is_valid('security_dashboard', $_POST['csrf_token'] ?? null)) {
        security_log_event('csrf_rejected', 'denied', ['context' => ['form' => 'security_dashboard']]);
        http_response_code(403);
        exit('Invalid or missing CSRF token.');
    }

    $alertId = filter_input(INPUT_POST, 'alert_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($alertId && $db) {
        $adminId = (int)$_SESSION['admin_id'];
        // Clearing open_key lets the same rule raise a new alert if the activity continues later.
        $stmt = $db->prepare("UPDATE security_alerts SET status = 'acknowledged', open_key = NULL, acknowledged_by = ?, acknowledged_at = NOW() WHERE id = ? AND status = 'open'");
        $stmt->bind_param('ii', $adminId, $alertId);
        $stmt->execute();
        if ($stmt->affected_rows === 1) {
            security_log_event('alert_acknowledged', 'success', ['target_type' => 'alert', 'target_id' => $alertId]);
        }
        $stmt->close();
    }
    header('Location: dashboard.php');
    exit;
}

function dashboard_date(string $name): ?string
{
    $value = trim((string)($_GET[$name] ?? ''));
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return ($date && $date->format('Y-m-d') === $value) ? $value : null;
}

function dashboard_outcome_badge(string $outcome): string
{
    return [
        'success' => 'badge-mint',
        'failure' => 'badge-danger',
        'denied'  => 'badge-danger',
        'blocked' => 'badge-warning',
        'info'    => 'badge-info',
    ][$outcome] ?? 'badge-info';
}

function dashboard_actor(array $event): string
{
    if ($event['actor_user_id'] === null) {
        return 'Not signed in';
    }
    return 'User #' . $event['actor_user_id'] . ($event['actor_role'] ? ' (' . $event['actor_role'] . ')' : '');
}

function dashboard_context(?string $json): string
{
    $values = $json ? json_decode($json, true) : null;
    if (!is_array($values)) {
        return '';
    }
    $parts = [];
    foreach ($values as $key => $value) {
        $parts[] = str_replace('_', ' ', $key) . ': ' . (is_bool($value) ? ($value ? 'yes' : 'no') : str_replace('_', ' ', (string)$value));
    }
    return implode(', ', $parts);
}

$filterType = array_key_exists($_GET['event_type'] ?? '', $catalog) ? $_GET['event_type'] : '';
$filterOutcome = in_array($_GET['outcome'] ?? '', SECURITY_OUTCOMES, true) ? $_GET['outcome'] : '';
$filterFrom = dashboard_date('date_from');
$filterTo = dashboard_date('date_to');
$page = max(1, (int)($_GET['page'] ?? 1));
$pageSize = (int)$settings['page_size'];
$dateError = (($_GET['date_from'] ?? '') !== '' && !$filterFrom) || (($_GET['date_to'] ?? '') !== '' && !$filterTo);

$where = [];
$types = '';
$params = [];
if ($filterType !== '') {
    $where[] = 'event_type = ?';
    $types .= 's';
    $params[] = $filterType;
}
if ($filterOutcome !== '') {
    $where[] = 'outcome = ?';
    $types .= 's';
    $params[] = $filterOutcome;
}
if ($filterFrom) {
    $where[] = 'created_at >= ?';
    $types .= 's';
    $params[] = $filterFrom . ' 00:00:00';
}
if ($filterTo) {
    $where[] = 'created_at < DATE_ADD(?, INTERVAL 1 DAY)';
    $types .= 's';
    $params[] = $filterTo . ' 00:00:00';
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

function dashboard_fetch(mysqli $db, string $sql, string $types = '', array $params = []): array
{
    $stmt = $db->prepare($sql);
    if ($types !== '') {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
}

$stats = ['failed_logins' => 0, 'denied' => 0, 'successful_logins' => 0, 'password_events' => 0];
$openAlerts = $failedLogins = $deniedEvents = $events = [];
$totalEvents = 0;

if ($db) {
    $stats = dashboard_fetch($db, "SELECT
            COALESCE(SUM(event_type IN ('login_failure', 'mfa_failure')), 0) AS failed_logins,
            COALESCE(SUM(event_type IN ('access_denied', 'csrf_rejected')), 0) AS denied,
            COALESCE(SUM(event_type = 'login_success'), 0) AS successful_logins,
            COALESCE(SUM(category = 'password'), 0) AS password_events
        FROM security_audit_log WHERE created_at > (NOW() - INTERVAL 24 HOUR)")[0];

    $openAlerts = dashboard_fetch($db, "SELECT * FROM security_alerts WHERE status = 'open' ORDER BY severity = 'high' DESC, last_seen_at DESC LIMIT 50");

    $columns = 'id, created_at, event_type, outcome, actor_user_id, actor_role, identifier_masked, ip_address, http_method, request_path, target_type, target_id, context';
    $failedLogins = dashboard_fetch($db, "SELECT $columns FROM security_audit_log WHERE event_type IN ('login_failure', 'mfa_failure') ORDER BY id DESC LIMIT 5");
    $deniedEvents = dashboard_fetch($db, "SELECT $columns FROM security_audit_log WHERE event_type IN ('access_denied', 'csrf_rejected') ORDER BY id DESC LIMIT 5");

    $totalEvents = (int)dashboard_fetch($db, "SELECT COUNT(*) AS total FROM security_audit_log $whereSql", $types, $params)[0]['total'];
    $offset = ($page - 1) * $pageSize;
    $events = dashboard_fetch(
        $db,
        "SELECT $columns FROM security_audit_log $whereSql ORDER BY id DESC LIMIT ? OFFSET ?",
        $types . 'ii',
        array_merge($params, [$pageSize, $offset])
    );
}
$totalPages = max(1, (int)ceil($totalEvents / $pageSize));

function dashboard_page_link(int $target): string
{
    $query = array_intersect_key($_GET, array_flip(['event_type', 'outcome', 'date_from', 'date_to']));
    $query['page'] = $target;
    return 'dashboard.php?' . http_build_query($query);
}

include '../../includes/header.php';
?>

<div class="admin-layout">
    <?php include '../../includes/sidebar.php'; ?>

    <main class="admin-content">
        <?php include '../../includes/navbar.php'; ?>

        <div class="page-header">
            <div>
                <span class="page-eyebrow">SYSTEM &amp; SECURITY</span>
                <h1>Security Monitor</h1>
                <p>Security events and alerts. Records are kept for <?= (int)$settings['retention_days'] ?> days.</p>
            </div>
        </div>

        <?php if (!$db) { ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>The security log is unavailable. Check the database connection.</span>
            </div>
        <?php } ?>

        <section class="mb-4" aria-labelledby="alerts-heading">
            <h2 id="alerts-heading" class="h5 fw-bold mb-3">Suspicious activity</h2>
            <?php if ($openAlerts) { ?>
                <?php foreach ($openAlerts as $alert) {
                    $rule = $settings['rules'][$alert['rule_key']] ?? null;
                ?>
                    <div class="alert <?= $alert['severity'] === 'high' ? 'alert-danger' : 'alert-warning' ?> d-flex justify-content-between align-items-start gap-3 flex-wrap">
                        <div>
                            <strong><?= htmlspecialchars($rule['label'] ?? $alert['rule_key']) ?></strong>
                            <span class="badge <?= $alert['severity'] === 'high' ? 'badge-danger' : 'badge-warning' ?> ms-1"><?= htmlspecialchars(ucfirst($alert['severity'])) ?></span>
                            <div class="small mt-1">
                                <?= htmlspecialchars($alert['subject_display']) ?>:
                                <?= (int)$alert['event_count'] ?> events in <?= (int)$alert['window_minutes'] ?> minutes
                                (threshold <?= (int)$alert['threshold_value'] ?>).
                                First seen <?= htmlspecialchars($alert['first_seen_at']) ?>, last seen <?= htmlspecialchars($alert['last_seen_at']) ?>.
                            </div>
                            <?php if ($rule) { ?>
                                <div class="small mt-1">Possible false positive: <?= htmlspecialchars($rule['false_positive']) ?></div>
                            <?php } ?>
                        </div>
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('security_dashboard')) ?>">
                            <input type="hidden" name="alert_id" value="<?= (int)$alert['id'] ?>">
                            <button type="submit" class="btn btn-secondary">Acknowledge</button>
                        </form>
                    </div>
                <?php } ?>
            <?php } else { ?>
                <p class="text-muted mb-0">No open alerts.</p>
            <?php } ?>
        </section>

        <div class="stats-grid">
            <?php foreach ([
                ['Failed logins', $stats['failed_logins'], 'bi-x-octagon'],
                ['Access denied', $stats['denied'], 'bi-slash-circle'],
                ['Successful logins', $stats['successful_logins'], 'bi-box-arrow-in-right'],
                ['Password events', $stats['password_events'], 'bi-key'],
            ] as [$title, $value, $icon]) { ?>
                <div class="stat-card">
                    <div>
                        <div class="stat-card-header">
                            <span class="stat-card-title"><?= $title ?></span>
                            <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
                        </div>
                        <div class="stat-value"><?= number_format((int)$value) ?></div>
                    </div>
                    <div class="stat-footer">Last 24 hours</div>
                </div>
            <?php } ?>
        </div>

        <div class="row g-4 mb-4">
            <?php foreach ([
                ['Recent failed logins', $failedLogins, 'login_failure'],
                ['Recent denied requests', $deniedEvents, 'access_denied'],
            ] as [$title, $rows, $linkType]) { ?>
                <section class="col-lg-6">
                    <div class="form-card h-100">
                        <h2 class="h6 fw-bold mb-3"><?= $title ?></h2>
                        <?php if ($rows) { ?>
                            <ul class="list-unstyled mb-2 small">
                                <?php foreach ($rows as $row) { ?>
                                    <li class="py-1 border-bottom">
                                        <span class="text-muted"><?= htmlspecialchars($row['created_at']) ?></span>
                                        <?= htmlspecialchars($row['identifier_masked'] ?? dashboard_actor($row)) ?>,
                                        <?= htmlspecialchars(security_mask_ip($row['ip_address'])) ?>,
                                        <code><?= htmlspecialchars($row['request_path']) ?></code>
                                    </li>
                                <?php } ?>
                            </ul>
                            <a class="small" href="dashboard.php?event_type=<?= $linkType ?>">View all</a>
                        <?php } else { ?>
                            <p class="text-muted small mb-0">None recorded.</p>
                        <?php } ?>
                    </div>
                </section>
            <?php } ?>
        </div>

        <section class="form-card" style="max-width: none;" aria-labelledby="events-heading">
            <h2 id="events-heading" class="h5 fw-bold mb-3">Audit events</h2>

            <?php if ($dateError) { ?>
                <div class="alert alert-danger"><span>Enter dates as YYYY-MM-DD. The invalid date was ignored.</span></div>
            <?php } ?>

            <form method="GET" class="row g-3 align-items-end mb-3">
                <div class="col-md-4">
                    <label class="form-label" for="event_type">Event type</label>
                    <select class="form-select" id="event_type" name="event_type">
                        <option value="">All events</option>
                        <?php foreach ($catalog as $type => [$label]) { ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= $filterType === $type ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="outcome">Outcome</label>
                    <select class="form-select" id="outcome" name="outcome">
                        <option value="">All</option>
                        <?php foreach (SECURITY_OUTCOMES as $outcome) { ?>
                            <option value="<?= $outcome ?>" <?= $filterOutcome === $outcome ? 'selected' : '' ?>><?= ucfirst($outcome) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_from">From</label>
                    <input class="form-control" type="date" id="date_from" name="date_from" value="<?= htmlspecialchars($filterFrom ?? '') ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label" for="date_to">To</label>
                    <input class="form-control" type="date" id="date_to" name="date_to" value="<?= htmlspecialchars($filterTo ?? '') ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a class="btn btn-secondary" href="dashboard.php">Clear</a>
                </div>
            </form>

            <p class="small text-muted"><?= number_format($totalEvents) ?> matching events</p>

            <?php if ($events) { ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle small">
                        <thead>
                            <tr>
                                <th scope="col">Time</th>
                                <th scope="col">Event</th>
                                <th scope="col">Outcome</th>
                                <th scope="col">User</th>
                                <th scope="col">Account</th>
                                <th scope="col">Address</th>
                                <th scope="col">Request</th>
                                <th scope="col">Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($events as $event) { ?>
                                <tr>
                                    <td class="text-nowrap"><?= htmlspecialchars($event['created_at']) ?></td>
                                    <td><?= htmlspecialchars($catalog[$event['event_type']][0] ?? $event['event_type']) ?></td>
                                    <td><span class="badge <?= dashboard_outcome_badge($event['outcome']) ?>"><?= htmlspecialchars(ucfirst($event['outcome'])) ?></span></td>
                                    <td class="text-nowrap"><?= htmlspecialchars(dashboard_actor($event)) ?></td>
                                    <td><?= htmlspecialchars($event['identifier_masked'] ?? '') ?></td>
                                    <td class="text-nowrap"><?= htmlspecialchars(security_mask_ip($event['ip_address'])) ?></td>
                                    <td><code><?= htmlspecialchars($event['http_method'] . ' ' . $event['request_path']) ?></code></td>
                                    <td>
                                        <?php if ($event['target_type']) { ?>
                                            <?= htmlspecialchars(ucfirst($event['target_type']) . ' #' . $event['target_id']) ?><br>
                                        <?php } ?>
                                        <?= htmlspecialchars(dashboard_context($event['context'])) ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1) { ?>
                    <nav class="d-flex justify-content-between align-items-center" aria-label="Audit event pages">
                        <?php if ($page > 1) { ?>
                            <a class="btn btn-secondary" href="<?= htmlspecialchars(dashboard_page_link($page - 1)) ?>">Previous</a>
                        <?php } else { ?>
                            <span></span>
                        <?php } ?>
                        <span class="small text-muted">Page <?= $page ?> of <?= $totalPages ?></span>
                        <?php if ($page < $totalPages) { ?>
                            <a class="btn btn-secondary" href="<?= htmlspecialchars(dashboard_page_link($page + 1)) ?>">Next</a>
                        <?php } else { ?>
                            <span></span>
                        <?php } ?>
                    </nav>
                <?php } ?>
            <?php } else { ?>
                <p class="text-muted mb-0">No events match these filters.</p>
            <?php } ?>
        </section>
    </main>
</div>

<?php include '../../includes/footer.php'; ?>
