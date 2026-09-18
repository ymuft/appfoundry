<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Users · AppFoundry</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="shell">
  <header class="topbar">
    <div><div class="brand"><a href="/">AppFoundry</a></div><div class="muted">User administration</div></div>
    <div><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div>
  </header>

  <section class="card">
    <h2>Create user</h2>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="/admin/users">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <div class="row">
        <div><label>Name</label><input name="name" required></div>
        <div><label>Email</label><input name="email" type="email" required></div>
        <div><label>Password</label><input name="password" type="password" minlength="12" required></div>
        <div><label>Role</label><select name="role"><option value="user">user</option><option value="manager">manager</option><option value="admin">admin</option></select></div>
      </div>
      <div class="spacer"></div>
      <button type="submit">Create user</button>
    </form>
  </section>

  <div class="spacer"></div>
  <section class="card">
    <h2>Users</h2>
    <table>
      <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th></tr></thead>
      <tbody>
      <?php foreach ($users as $account): ?>
        <tr>
          <td><?= htmlspecialchars($account['name'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><?= htmlspecialchars($account['email'], ENT_QUOTES, 'UTF-8') ?></td>
          <td><span class="role"><?= htmlspecialchars($account['role'], ENT_QUOTES, 'UTF-8') ?></span></td>
          <td><?= (bool) $account['is_active'] ? 'active' : 'disabled' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </section>
</div>
</body>
</html>
