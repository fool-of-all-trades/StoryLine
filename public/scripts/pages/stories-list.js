let page = 1;
const limit = 10;
let loading = false;
let noMore = false;

// Parse URL parameters
function getStoriesParams() {
  const params = new URLSearchParams(location.search);
  const sort = params.get("sort") || "new";
  const dateParam = params.get("date");
  const date =
    dateParam && /^\d{4}-\d{2}-\d{2}$/.test(dateParam) ? dateParam : "today";

  return { sort, date };
}

// Fetch quote for the selected date
async function fetchQuote(date) {
  const endpoint =
    date === "today"
      ? "/api/quote/today"
      : `/api/quote?date=${encodeURIComponent(date)}`;

  let res = await fetch(endpoint, { credentials: "include" });

  // Create quote if it doesn't exist
  if (res.status === 404 || res.status === 500) {
    await fetch(endpoint, {
      method: "POST",
      credentials: "include",
      headers: { "X-CSRF-Token": window.CSRF_TOKEN },
    });
    res = await fetch(endpoint, { credentials: "include" });
  }

  return res;
}

// Load and display quote for the current date
async function loadQuoteForDate(date) {
  const quoteWrapper = document.querySelector("[data-quote-wrapper]");
  const quoteTextEl = document.querySelector("[data-quote-of-day]");
  const quoteMetaEl = document.querySelector("[data-quote-of-day-meta]");

  if (!quoteTextEl || !quoteMetaEl) return;

  try {
    const res = await fetchQuote(date);

    if (!res.ok) return;

    const quote = await res.json();

    quoteTextEl.textContent = `"${quote.sentence}"`;
    quoteMetaEl.textContent =
      [quote.source_book, quote.source_author].filter(Boolean).join(" — ") ||
      "—";

    if (quoteWrapper) {
      quoteWrapper.hidden = false;
    }
  } catch (err) {
    console.error("Error loading quote for stories day:", err);
  }
}

// Render author information
function renderAuthor(story) {
  // just plain text, no link to profile
  if (story.is_anonymous) {
    return "Author: Anonymous · ";
  }

  // logged-in user, with link to profile
  if (story.user_public_id) {
    const username = escapeHtml(story.username ?? "user");
    return `Author: <a href="/user/${story.user_public_id}">${username}</a> · `;
  }

  // logged-out user, no link to profile, but we have a nick he provided
  if (story.guest_name) {
    return `Author: ${escapeHtml(story.guest_name)} · `;
  }

  // no author info at all
  return "Author: Anonymous · ";
}

// Render a single story item
function renderStoryItem(story) {
  const li = document.createElement("li");
  li.className = "story";

  const title = story.title ? escapeHtml(story.title) : "(no title)";
  const preview = escapeHtml(story.content).slice(0, 180);
  const needsEllipsis = story.content.length > 180;

  li.innerHTML = `
    <a href="/story/${story.story_public_id}" class="title">${title}</a>
    <div class="meta">
      ${renderAuthor(story)}
      ${story.word_count ?? 0} words · <span data-count>${
    story.flower_count ?? 0
  }</span> 🌸
    </div>
    <p class="preview">${preview}${needsEllipsis ? "…" : ""}</p>
    <button class="like" data-like data-story="${story.id}">🌸 Flower</button>
  `;

  return li;
}

// Load a page of stories
async function loadStoriesPage(date, sort) {
  const list = document.querySelector("#stories-list");
  const loadMoreBtn = document.querySelector("[data-loadmore]");

  if (loading || noMore) return;

  loading = true;

  if (loadMoreBtn) {
    loadMoreBtn.disabled = true;
    loadMoreBtn.textContent = "Loading...";
  }

  try {
    const res = await fetch(
      `/api/stories?date=${encodeURIComponent(date)}&sort=${encodeURIComponent(
        sort
      )}&page=${page}&limit=${limit}`,
      { credentials: "include" }
    );

    const data = await res.json();
    const items = data.items || [];

    // Update story count
    const totalForDay =
      typeof data.total_for_day === "number" ? data.total_for_day : null;
    const countEl = document.querySelector("[data-stories-count]");
    if (countEl && totalForDay !== null) {
      countEl.textContent = totalForDay;
    }

    // Clear list on first page
    if (page === 1) {
      list.innerHTML = "";
    }

    // Handle empty results
    if (!items.length && page === 1) {
      list.innerHTML = "<li>No stories for this day yet.</li>";
      noMore = true;
    } else {
      // Render stories
      items.forEach((story) => {
        const storyItem = renderStoryItem(story);
        list.appendChild(storyItem);
      });

      // Check if there are more pages
      if (items.length < limit) {
        noMore = true;
      } else {
        page += 1;
      }
    }

    // Update load more button
    if (loadMoreBtn) {
      if (noMore) {
        loadMoreBtn.disabled = true;
        loadMoreBtn.textContent = "No more stories";
      } else {
        loadMoreBtn.disabled = false;
        loadMoreBtn.textContent = "Load more";
      }
    }
  } catch (err) {
    console.error("Error loading stories:", err);
    if (list) {
      list.innerHTML = "<li>Failed to load stories. Please try again.</li>";
    }
  } finally {
    loading = false;
  }
}

// Handle flower button click
async function handleFlowerClick(e) {
  const btn = e.target.closest("[data-like]");
  if (!btn) return;

  const storyId = btn.getAttribute("data-story");

  try {
    const res = await fetch(
      `/api/story/flower?id=${encodeURIComponent(storyId)}`,
      {
        method: "POST",
        credentials: "include",
        headers: { "X-CSRF-Token": window.CSRF_TOKEN },
      }
    );

    const data = await res.json();

    if (res.ok) {
      const countEl = btn.parentElement.querySelector("[data-count]");
      if (countEl) {
        countEl.textContent = data.count;
      }
    } else if (data?.error === "unauthorized") {
      location.href = "/login";
    } else {
      alert(`Error: ${data.error || "unknown"}`);
    }
  } catch (err) {
    console.error("Error updating flower count:", err);
    alert("Failed to update flower count. Please try again.");
  }
}

// Initialize stories list page
function initStoriesListPage() {
  const list = document.querySelector("#stories-list");
  if (!list) return;

  const loadMoreBtn = document.querySelector("[data-loadmore]");
  const { sort, date } = getStoriesParams();

  // Load initial content
  loadQuoteForDate(date);
  loadStoriesPage(date, sort);

  // Setup load more button
  if (loadMoreBtn) {
    loadMoreBtn.addEventListener("click", () => loadStoriesPage(date, sort));
  }

  // Setup flower button handler
  list.addEventListener("click", handleFlowerClick);
}

// ===== INITIALIZATION =====

initStoriesListPage();
