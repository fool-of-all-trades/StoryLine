const { escapeHtml } = window.App;

(async () => {
  const userMain = document.querySelector("main[data-user-public-id]");
  if (userMain) {
    const userPid = userMain.dataset.userPublicId;
    const wordsEl = document.querySelector("#user-word-stats");
    const countEl = document.querySelector("#user-stories-count");
    const listEl = document.querySelector("#user-stories-list");
    const searchInput = document.querySelector("#user-stories-search");

    const leftButton = document.querySelector(".left-arrow");
    const rightButton = document.querySelector(".right-arrow");

    let page = 1;
    const limit = 8;
    let totalStories = 0;
    let currentItems = [];

    // load profile data (without stories list)
    try {
      const res = await fetch(
        `/api/user/${encodeURIComponent(userPid)}/profile`,
        {
          credentials: "include",
        }
      );
      const payload = await res.json();
      if (!res.ok) {
        wordsEl.textContent = "Couldn't load your stats right now.";
        console.error("Profile error:", payload);
      } else {
        const { total_words, total_stories } = payload.data || {};

        // 1) number of stories and words
        if (typeof total_stories === "number" && countEl) {
          countEl.textContent = String(total_stories);
          totalStories = total_stories;
        }

        if (typeof total_words === "number" && wordsEl) {
          let label = "words";
          if (total_words === 1) label = "word";
          wordsEl.innerHTML =
            `You've written <strong>${total_words}</strong> ${label} all together!<br/>` +
            `I'm proud of you.`;
        }
      }
    } catch (e) {
      console.error("Failed to load user profile data", e);
      if (wordsEl) {
        wordsEl.textContent = "Couldn't load your stats right now.";
      }
    }

    // Load stories list
    async function loadStoriesPage(newPage) {
      try {
        const res = await fetch(
          `/api/user/${encodeURIComponent(
            userPid
          )}/stories?page=${newPage}&limit=${limit}`,
          { credentials: "include" }
        );
        const payload = await res.json();

        if (!res.ok) {
          console.error("Profile stories error:", payload);
          if (listEl) {
            listEl.innerHTML = "<p>Couldn't load your stories.</p>";
          }
          return;
        }

        page = payload.page || newPage;

        const storiesPayload = payload.stories || {};
        const items = Array.isArray(storiesPayload.items)
          ? storiesPayload.items
          : [];
        currentItems = items;

        if (listEl) {
          renderStories(listEl, items);
        }

        updateArrows();
      } catch (e) {
        console.error("Failed to load stories page", e);
        if (listEl) {
          listEl.innerHTML = "<p>Couldn't load your stories.</p>";
        }
      }
    }

    function updateArrows() {
      const totalPages = totalStories > 0 ? Math.ceil(totalStories / limit) : 1;

      if (leftButton) {
        leftButton.disabled = page <= 1;
      }
      if (rightButton) {
        rightButton.disabled = page >= totalPages;
      }
    }

    // Simple search filter by the title/content
    if (searchInput && listEl) {
      searchInput.addEventListener("input", () => {
        const q = searchInput.value.toLowerCase().trim();
        const filtered = currentItems.filter((s) => {
          const title = (s.title || "").toLowerCase();
          const content = (s.content || "").toLowerCase();
          return !q || title.includes(q) || content.includes(q);
        });
        renderStories(listEl, filtered);
      });
    }

    // Buttons
    if (leftButton) {
      leftButton.addEventListener("click", () => {
        console.log("Left button clicked, current page:", page);
        if (page > 1) {
          loadStoriesPage(page - 1);
        }
      });
    }
    if (rightButton) {
      rightButton.addEventListener("click", () => {
        const totalPages =
          totalStories > 0 ? Math.ceil(totalStories / limit) : 1;
        if (page < totalPages) {
          loadStoriesPage(page + 1);
        }
      });
    }

    // Load the first page
    if (listEl) {
      loadStoriesPage(page);
    }
  }

  function renderStories(container, items) {
    container.innerHTML = "";
    if (!items.length) {
      container.innerHTML =
        "<p>You haven't written any stories yet. Let's fix that!</p>";
      return;
    }

    items.forEach((item) => {
      const card = document.createElement("div");
      card.className = "book-card";

      const title = item.title ? escapeHtml(item.title) : "(no title)";
      const createdAt = item.created_at
        ? new Date(item.created_at).toLocaleDateString()
        : "";
      const words = item.word_count ?? 0;
      const flowers = item.flower_count ?? 0;

      card.innerHTML = `
        <a href="/story/${item.story_public_id}" class="book-card-title">${title}</a>
        <div class="book-card-meta">
          <span>${createdAt}</span> ·
          <span>${words} words</span> ·
          <span>${flowers} 🌸</span>
        </div>
      `;

      container.appendChild(card);
    });
  }

  // ===== FAVORITE QUOTE FORM =====
  const favForm = document.querySelector("#favorite-quote-form");
  if (favForm) {
    const msgEl = document.querySelector("#favorite-quote-message");

    favForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      msgEl && (msgEl.textContent = "");

      const formData = new FormData(favForm);

      try {
        const res = await fetch("/api/me/favorite-quote", {
          method: "POST",
          body: formData,
          credentials: "include",
          headers: {
            "X-CSRF-Token": window.CSRF_TOKEN || "",
          },
        });

        const data = await res.json();

        if (res.ok) {
          if (msgEl) {
            msgEl.textContent = "Favorite quote saved ✨";
            msgEl.classList.remove("error");
            msgEl.classList.add("success");
          }
        } else {
          const err = data?.error || "unknown_error";
          if (msgEl) {
            msgEl.textContent = "Could not save favorite quote: " + err;
            msgEl.classList.remove("success");
            msgEl.classList.add("error");
          }
        }
      } catch (err) {
        console.error("favorite-quote error", err);
        if (msgEl) {
          msgEl.textContent = "Unexpected error. Please try again.";
          msgEl.classList.remove("success");
          msgEl.classList.add("error");
        }
      }
    });
  }
})();
