<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign in · AppFoundry</title>
  <link rel="stylesheet" href="/assets/app.css">
</head>
<body>
<div class="login-wrap">
  <div class="card login-card">
    <div class="brand">AppFoundry</div>
    <p class="muted">Secure internal-app foundation.</p>
    <?php if ($error): ?><div class="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form method="post" action="/login" autocomplete="on">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" required autocomplete="username">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" required autocomplete="current-password">
      <div class="spacer"></div>
      <button type="submit">Sign in</button>
    </form>
  </div>
</div>
</body>
</html>
