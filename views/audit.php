<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Audit log · AppFoundry</title>
    <link rel="stylesheet" href="/assets/app.css">
</head>

<body>
    <div class="shell">
        <header class="topbar">
            <div>
                <div class="brand"><a href="/">AppFoundry</a></div>
                <div class="muted">Audit log</div>
            </div>
            <div>
                <span class="muted"><?= htmlspecialchars($user['email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                · <a href="/">Dashboard</a>
                · <a href="/admin/users">Users</a>
            </div>
        </header>

        <section class="card">
            <h2>Audit log</h2>
            <p class="muted">Page <?= (int) $page ?></p>

            <?php
            $pagination = static function () use ($page, $events): void { ?>
                <?php if ($page > 1): ?>
                    <a href="/admin/audit?page=<?= (int) ($page - 1) ?>">← Newer</a>
                <?php endif; ?>
                <?php if ($events !== []): ?>
                    <a href="/admin/audit?page=<?= (int) ($page + 1) ?>" style="margin-left: 12px;">Older →</a>
                <?php endif; ?>
            <?php }; ?>

            <?php if ($events === [] && $page === 1): ?>
                <p class="muted">No events recorded yet.</p>
            <?php elseif ($events === []): ?>
                <p class="muted">No events on this page.</p>
                <?php if ($page > 1): ?>
                    <a href="/admin/audit?page=<?= (int) ($page - 1) ?>">← Newer</a>
                <?php endif; ?>
            <?php else: ?>
                <div>
                    <?php $pagination(); ?>
                </div>

                <div class="spacer"></div>

                <table>
                    <thead>
                        <tr>
                            <th>When</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>IP</th>
                            <th>Metadata</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td class="code"><?= htmlspecialchars((string) $event['created_at'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                <td><span class="role"><?= htmlspecialchars((string) $event['action'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span></td>
                                <td><?= $event['user_id'] !== null ? (int) $event['user_id'] : '—' ?></td>
                                <td class="code"><?= htmlspecialchars((string) ($event['ip_address'] ?? '—'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                                <td class="code"><?= htmlspecialchars((string) ($event['metadata'] ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="spacer"></div>
                <div>
                    <?php $pagination(); ?>
                </div>
            <?php endif; ?>
        </section>
    </div>
</body>

</html>