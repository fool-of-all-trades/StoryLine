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

function isPublicUuid(value) {
  return typeof value === "string" && /^[0-9a-fA-F-]{36}$/.test(value);
}

function safeCount(value) {
  const count = Number(value);
  return Number.isFinite(count) ? count : 0;
}

function setListMessage(list, message) {
  const li = document.createElement("li");
  li.textContent = message;
  list.replaceChildren(li);
}

// Fetch quote for the selected date
async function fetchQuote(date) {
  const endpoint =
    date === "today"
      ? "/api/quote/today"
      : `/api/quote?date=${encodeURIComponent(date)}`;

  return fetch(endpoint, { credentials: "include" });
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
      [quote.source_book, quote.source_author].filter(Boolean).join(" \u2014 ") ||
      "\u2014";

    if (quoteWrapper) {
      quoteWrapper.hidden = false;
    }
  } catch (err) {
    console.error("Error loading quote for stories day:", err);
  }
}

function appendAuthor(meta, story) {
  meta.append("Author: ");

  if (story.is_anonymous) {
    meta.append("Anonymous", " \u00b7 ");
    return;
  }

  if (story.user_public_id) {
    const username = story.username ?? "user";

    if (isPublicUuid(story.user_public_id)) {
      const authorLink = document.createElement("a");
      authorLink.href = `/user/${encodeURIComponent(story.user_public_id)}`;
      authorLink.textContent = username;
      meta.append(authorLink, " \u00b7 ");
    } else {
      meta.append(username, " \u00b7 ");
    }

    return;
  }

  meta.append("Anonymous", " \u00b7 ");
}

// Render a single story item
function renderStoryItem(story) {
  const li = document.createElement("li");
  li.className = "story";

  const storyId = story.story_public_id;
  const hasStoryLink = isPublicUuid(storyId);
  const title = document.createElement(hasStoryLink ? "a" : "span");
  title.className = "title";
  title.textContent = story.title || "(no title)";

  if (hasStoryLink) {
    title.href = `/story/${encodeURIComponent(storyId)}`;
  }

  const meta = document.createElement("div");
  meta.className = "meta";
  appendAuthor(meta, story);

  const count = document.createElement("span");
  count.dataset.count = "";
  count.textContent = String(safeCount(story.flower_count));
  meta.append(`${safeCount(story.word_count)} words \u00b7 `, count, " \ud83c\udf38");

  const preview = document.createElement("p");
  preview.className = "preview";
  const content = String(story.content ?? "");
  preview.textContent =
    content.slice(0, 180) + (content.length > 180 ? "\u2026" : "");

  li.append(title, meta, preview);

  return li;
}

// Load a page of stories
async function loadStoriesPage(date, sort) {
  const list = document.querySelector("#stories-list");
  const loadMoreBtn = document.querySelector("[data-loadmore]");

  if (!list || loading || noMore) return;

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

    if (!res.ok) {
      // 401/403 -> maybe redirect to login (or show a message)
      if (res.status === 401 || res.status === 403) {
        location.href = "/login";
        return;
      }
      const text = await res.text();
      console.error("Stories API error:", res.status, text);
      throw new Error(`Stories API ${res.status}`);
    }

    // Ensure it's JSON before parsing
    const ct = res.headers.get("content-type") || "";
    if (!ct.includes("application/json")) {
      const text = await res.text();
      console.error("Expected JSON, got:", text.slice(0, 500));
      throw new Error("Non-JSON response");
    }

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
      list.replaceChildren();
    }

    // Handle empty results
    if (!items.length && page === 1) {
      setListMessage(list, "No stories for this day yet.");
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
    setListMessage(list, "Failed to load stories. Please try again.");
  } finally {
    loading = false;
  }
}

// Handle flower button click
async function handleFlowerClick(e) {
  const btn = e.target.closest("[data-like]");
  if (!btn) return;

  const storyPublicId = btn.getAttribute("data-story");

  try {
    const res = await fetch(
      `/api/story/${encodeURIComponent(storyPublicId)}/flower`,
      {
        method: "POST",
        credentials: "include",
        headers: {
          "X-CSRF-Token": window.CSRF_TOKEN,
        },
      }
    );

    const data = await res.json();

    if (res.ok) {
      const countEl = btn.parentElement.querySelector("[data-count]");
      if (countEl) {
        countEl.textContent = data.count;
      }
    } else if (
      data?.error === "authentication_required" ||
      data?.error === "unauthorized"
    ) {
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
