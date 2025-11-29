<?php
if (empty($_COOKIE['device_token'])) {
    try {
        $token = bin2hex(random_bytes(16)); // 32-char hex
    } catch (\Throwable $e) {
        // fallback
        $token = bin2hex(uniqid('', true));
    }

    $params = [
        'expires'  => time() + 60 * 60 * 24 * 365, // ~1 year
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => false,
        'samesite' => 'Lax',
    ];

    setcookie('device_token', $token, $params);
    // so that a not logged in user can't create more than 1 story per day from the same browser session/device
    $_COOKIE['device_token'] = $token;
}
?>

<!doctype html>
<html lang="pl">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <meta name="csrf-token" content="<?= htmlspecialchars(App\Security\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

  <title><?= $title ?? 'StoryLine' ?></title>

  <link rel="stylesheet" href="/styles/main.css">

  <!-- global scripts -->
  <script defer src="/scripts/csrf.js"></script>
  <script defer src="/scripts/utils.js"></script>

  <!-- only for admin charts -->
  <?php if (!empty($includeCharts)): ?>
    <script defer src="/scripts/chart.js"></script>
  <?php endif; ?>

  <?php
  // $pageScripts is set in a view file to include page-specific JS files
  if (!empty($pageScripts) && is_array($pageScripts)): 
    foreach ($pageScripts as $script):
  ?>
      <script defer src="/scripts/<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>.js"></script>
  <?php
    endforeach;
  endif;
  ?>
</head>
<body>
  <nav class="nav">
    <a href="/dashboard" class="logo">StoryLine</a>
    <div class="spacer"></div>
    <a href="/stories/today">Stories</a>

    <?php $current_user = current_user(); ?>

    <?php if (is_logged_in()): ?>

      <a href="/user/<?= htmlspecialchars($current_user['public_id'], ENT_QUOTES, 'UTF-8') ?>">My profile</a>

      <?php if (is_admin()): ?>
        <a href="/admin">Admin</a>
      <?php endif; ?>

      <form method="post" action="/logout">
        <?= App\Security\Csrf::inputField() ?>
        <button class="linklike">Logout (<?= htmlspecialchars($current_user['username'], ENT_QUOTES, 'UTF-8') ?>)</button>
      </form>

    <?php else: ?>

  <a href="/login">Login</a>

<?php endif; ?>


  </nav>
  <main class="container">
