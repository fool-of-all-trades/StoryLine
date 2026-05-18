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

<!DOCTYPE html>
<html lang="pl">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta
      name="csrf-token"
      content="<?= htmlspecialchars(App\Security\Csrf::token(), ENT_QUOTES, 'UTF-8') ?>"
    />

    <title><?= $title ?? 'StoryLine' ?></title>

    <link rel="stylesheet" href="/styles/main.css" />

    <?php
    // $pageStyles is set in a view file to include page-specific CSS files
    if (!empty($pageStyles) && is_array($pageStyles)):
      foreach ($pageStyles as $style):
    ?>
      <link
        rel="stylesheet"
        href="/styles/<?= htmlspecialchars($style, ENT_QUOTES, 'UTF-8') ?>.css"
      />
    <?php
      endforeach;
    endif;
    ?>

    <!-- global scripts -->
    <script defer src="/scripts/csrf.js"></script>
    <script defer src="/scripts/utils.js"></script>
    <script defer src="/scripts/nav.js"></script>

    <!-- only for admin charts -->
    <?php if (!empty($includeCharts)): ?>
    <script defer src="/scripts/chart.js"></script>
    <?php endif; ?>

    <?php
    // $pageScripts is set in a view file to include page-specific JS files
    if (!empty($pageScripts) && is_array($pageScripts)): 
        foreach ($pageScripts as $script):
    ?>
    <script
      defer
      src="/scripts/<?= htmlspecialchars($script, ENT_QUOTES, 'UTF-8') ?>.js"
    ></script>
    <?php
    endforeach;
  endif;
  ?>
  </head>
  <body>
    <button class="hamburger" aria-controls="nav" aria-expanded="false">
      ☰
    </button>

    <div class="parent">
      <nav class="div1 nav">
        <a id="storyline-icon" class="tip" data-tip="Dashboard" href="/dashboard" class="logo">
          <div aria-hidden="true"></div>
          <span class="nav-label">Dashboard</span>
        </a>
        <div class="spacer"></div>
        <a id="stories-icon" class="tip" data-tip="Stories" href="/stories/today">
          <div aria-hidden="true"></div>
          <span class="nav-label">Stories</span>
        </a>

        <?php $current_user = current_user(); ?>

        <?php if (is_logged_in()): ?>

        <a id="my-profile-link" class="tip" data-tip="Profile"
          href="/user/<?= htmlspecialchars($current_user['public_id'], ENT_QUOTES, 'UTF-8') ?>"
          >
          <div aria-hidden="true"></div>
          <span class="nav-label">Profile</span>
          </a
        >

        <?php if (is_admin()): ?>
        <a id="admin-icon" class="tip" data-tip="Admin" href="/admin">
          <div aria-hidden="true"></div>
          <span class="nav-label">Admin</span>
        </a>
        <?php endif; ?>

        <form method="post" action="/logout">
          <?= App\Security\Csrf::inputField() ?>
          <button id="logout-icon" class="tip" data-tip="Logout" class="linklike">
            <div aria-hidden="true"></div>
            <span class="nav-label">Logout</span>
          </button>
        </form>

        <?php else: ?>

        <a id="login-icon" class="tip" data-tip="Login" href="/login">
          <div aria-hidden="true"></div>
          <span class="nav-label">Login</span>
        </a>

        <?php endif; ?>
      </nav>
