<?php 
  $title = "StoryLine — Today"; 
  $pageScripts = ['pages/dashboard'];
  $pageStyles = ['dashboard'];
  include __DIR__."/partials/header.php"; 
  use App\Security\Csrf;
?>

    <div class="dashboard-content">
        <div class="div2 prompt-card">
          <h2 class="quote" data-quote></h2>
        </div>
        <div class="div3 prompt-card meta" data-meta>
        </div>
        <div class="div4 writer" id="write">
          <form id="story-form" method="post" action="/api/story">
            <?= Csrf::inputField() ?>
            <input
              type="text"
              id="title"
              name="title"
              placeholder="Title"
              maxlength="100"
            />

            <textarea
              id="story-textarea"
              name="content"
              rows="20"
              data-wordlimit="500"
              placeholder="Include the sentence of the day in your story..."
              data-challenge-id="<?= htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8') ?>"
            ></textarea>

            <div class="story-bottom-div">
              <label class="pretty-check">
                <input type="checkbox" name="anonymous" value="1" />
                <span class="box" aria-hidden="true"></span>
                <span class="text">Anonymous</span>
              </label>
            </div>
            <br />
            <p id="story-message" class="form-message"></p>
          </form>
        </div>
        <div class="div5">
          <p id="word-counter"><span id="word-count-span">0</span>/500 </br> words</p>
        </div>
        <div class="div6">
          <button type="submit" class="btn primary" form="story-form">
            
          </button>
        </div>
      </div>
    </div>

    <div class="backdrop" hidden></div>

    
  </body>
</html>
