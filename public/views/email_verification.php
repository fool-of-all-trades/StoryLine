<?php
  $title = 'StoryLine - Email verification';
  $pageStyles = ['login'];
  include __DIR__ . '/partials/header.php';

  $status = $status ?? 'error';
  $code = $code ?? null;
  $message = $message ?? match ($code) {
      'invalid_or_expired_token' => 'This verification link is invalid or has expired.',
      'too_many_requests' => 'Too many attempts. Please wait a bit and try again.',
      'second_factor_required' => 'Email verified. Please log in to continue.',
      default => 'Something went wrong. Please try again later.',
  };
?>

      <div class="auth-content">
        <div class="auth-content-div">
          <h1>Email verification</h1>
          <p class="form-message <?= $status === 'success' ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
          </p>
          <p><a href="/login">Go to login</a></p>
        </div>
      </div>
    </div>
    <div class="backdrop" hidden></div>
  </body>
</html>
