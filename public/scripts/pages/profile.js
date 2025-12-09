let page = 1;
const limit = 8;
let totalStories = 0;
let currentItems = [];

// Load user profile stats (without stories list)
async function loadUserProfile(userPid) {
  const wordsEl = document.querySelector("#user-word-stats");
  const countEl = document.querySelector("#user-stories-count");

  try {
    const res = await fetch(
      `/api/user/${encodeURIComponent(userPid)}/profile`,
      { credentials: "include" }
    );

    const payload = await res.json();

    if (!res.ok) {
      if (wordsEl) {
        wordsEl.textContent = "Couldn't load your stats right now.";
      }
      console.error("Profile error:", payload);
      return;
    }

    const { total_words, total_stories } = payload.data || {};

    // total number of stories and words
    if (typeof total_stories === "number" && countEl) {
      countEl.textContent = String(total_stories);
      totalStories = total_stories;
    }

    if (typeof total_words === "number" && wordsEl) {
      const label = total_words === 1 ? "word" : "words";
      wordsEl.innerHTML =
        `You've written <strong>${total_words}</strong> ${label} all together!<br/>` +
        `I'm proud of you.`;
    }
  } catch (e) {
    console.error("Failed to load user profile data", e);
    if (wordsEl) {
      wordsEl.textContent = "Couldn't load your stats right now.";
    }
  }
}

// Load stories for a given page
async function loadStoriesPage(userPid, newPage) {
  const listEl = document.querySelector("#user-stories-list");

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

    updatePaginationArrows();
  } catch (e) {
    console.error("Failed to load stories page", e);
    if (listEl) {
      listEl.innerHTML = "<p>Couldn't load your stories.</p>";
    }
  }
}

// Update pagination arrow states
function updatePaginationArrows() {
  const leftButton = document.querySelector(".left-arrow");
  const rightButton = document.querySelector(".right-arrow");
  const totalPages = totalStories > 0 ? Math.ceil(totalStories / limit) : 1;

  if (leftButton) {
    leftButton.disabled = page <= 1;
  }
  if (rightButton) {
    rightButton.disabled = page >= totalPages;
  }
}

// Render story cards in the given container
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

// Simple search filter by the title/content
function handleStoriesSearch(e) {
  const listEl = document.querySelector("#user-stories-list");
  const query = e.target.value.toLowerCase().trim();

  const filtered = currentItems.filter((story) => {
    const title = (story.title || "").toLowerCase();
    const content = (story.content || "").toLowerCase();
    return !query || title.includes(query) || content.includes(query);
  });

  renderStories(listEl, filtered);
}

// Handle pagination clicks
function handlePreviousPage(userPid) {
  if (page > 1) {
    loadStoriesPage(userPid, page - 1);
  }
}

function handleNextPage(userPid) {
  const totalPages = totalStories > 0 ? Math.ceil(totalStories / limit) : 1;
  if (page < totalPages) {
    loadStoriesPage(userPid, page + 1);
  }
}

// Initialize user profile page
function initUserProfilePage() {
  const userMain = document.querySelector("main[data-user-public-id]");
  if (!userMain) return;

  const userPid = userMain.dataset.userPublicId;
  const searchInput = document.querySelector("#user-stories-search");
  const leftButton = document.querySelector(".left-arrow");
  const rightButton = document.querySelector(".right-arrow");
  const listEl = document.querySelector("#user-stories-list");

  // Load profile data
  loadUserProfile(userPid);

  // Load first page of stories
  if (listEl) {
    loadStoriesPage(userPid, page);
  }

  // Setup search
  if (searchInput) {
    searchInput.addEventListener("input", handleStoriesSearch);
  }

  // Setup pagination
  if (leftButton) {
    leftButton.addEventListener("click", () => handlePreviousPage(userPid));
  }
  if (rightButton) {
    rightButton.addEventListener("click", () => handleNextPage(userPid));
  }
}

// ===== FAVORITE QUOTE FORM =====

async function handleFavoriteQuoteSubmit(e) {
  e.preventDefault();

  const msgEl = document.querySelector("#favorite-quote-message");
  const form = e.target;

  showMsg(msgEl, "", null);

  const formData = new FormData(form);

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
      showMsg(msgEl, "Favorite quote saved ✨", "success");
    } else {
      const error = data?.error || "unknown_error";
      showMsg(msgEl, `Could not save favorite quote: ${error}`, "error");
    }
  } catch (err) {
    console.error("Favorite quote error:", err);
    showMsg(msgEl, "Unexpected error. Please try again.", "error");
  }
}

function initFavoriteQuoteForm() {
  const favForm = document.querySelector("#favorite-quote-form");
  if (!favForm) return;

  favForm.addEventListener("submit", handleFavoriteQuoteSubmit);
}

// ===== INITIALIZATION =====
initUserProfilePage();
initFavoriteQuoteForm();
