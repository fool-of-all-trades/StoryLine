<?php 
  $title = "StoryLine — Today"; 
  $pageScripts = ['pages/dashboard'];
  include __DIR__."/partials/header.php"; 
  use App\Security\Csrf;
?>

    <section class="prompt-card">
      <small class="date" data-date></small>
      <h1 class="quote" data-quote></h1>
      <small class="meta" data-meta></small>
    </section>

    <section id="write" class="writer">
      <form id="story-form" method="post" action="/api/story">
        <?= Csrf::inputField() ?>
        <input type="text" id="title" name="title" placeholder="Title" maxlength="100" />
        <br>
        
        <?php if (!$current_user) : ?>
          <input
            type="text"
            id="guest-name"
            name="guest_name"
            placeholder="Your name (optional)"
            maxlength="60"
          />
          <br>
        <?php endif; ?>

        <p id="word-counter">
          <span id="word-count-span">0</span>/500 words
        </p>

        <textarea
          id="story-textarea"
          name="content"
          rows="12"
          data-wordlimit="500"
          data-challenge-id="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
        ></textarea>
        <br>
        <label>
          <input type="checkbox" name="anonymous" value="1">
          Anonymous
        </label>
        <br>
        <button type="submit" class="btn primary">Share</button>
        <p id="story-message" class="form-message"></p>
      </form>
    </section>

  </main>
</body>
</html>

