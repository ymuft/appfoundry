<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AppFoundry</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="shell">
  <header class="topbar">
    <div><div class="brand">AppFoundry</div><div class="muted">starter workspace</div></div>
    <div>
      <span class="muted"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></span>
      <?php if ($user['role'] === 'admin'): ?> · <a href="/admin/users">Users</a> · <a href="/admin/audit">Audit</a><?php endif; ?>
      · <form class="inline" method="post" action="/logout"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>"><button class="secondary" type="submit">Sign out</button></form>
    </div>
  </header>

  <div class="grid">
    <section class="card"><div class="muted">Authentication</div><div class="stat">Ready</div><p class="muted">Sessions, CSRF and login throttling are enabled.</p></section>
    <section class="card"><div class="muted">Authorization</div><div class="stat"><?= htmlspecialchars(strtoupper($user['role']), ENT_QUOTES, 'UTF-8') ?></div><p class="muted">Admin, manager and user roles are built in.</p></section>
    <section class="card"><div class="muted">Health</div><div class="stat">/health</div><p class="muted">Use the JSON health endpoint for uptime checks.</p></section>
  </div>

  <div class="spacer"></div>
  <section class="card">
    <h2>Build your app from here</h2>
    <p class="muted">Replace this dashboard with your business workflow while keeping the security and infrastructure foundation underneath it.</p>
  </section>
</div>
</body>
</html>
