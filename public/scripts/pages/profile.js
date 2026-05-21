let page = 1;
const limit = 8;
let totalStories = 0;
let currentItems = [];
let isOwnProfile = false;

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
      const strong = document.createElement("strong");
      strong.textContent = String(total_words);
      wordsEl.replaceChildren(
        "You've written ",
        strong,
        ` ${label} all together!`
      );
    }
  } catch (e) {
    console.error("Failed to load user profile data", e);
    if (wordsEl) {
      wordsEl.textContent = "Couldn't load your stats right now.";
    }
  }
}

function setStoryListMessage(listEl, message) {
  const messageEl = document.createElement("p");
  messageEl.textContent = message;
  listEl.replaceChildren(messageEl);
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
        setStoryListMessage(listEl, "Couldn't load your stories.");
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
      setStoryListMessage(listEl, "Couldn't load your stories.");
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

function isPublicUuid(value) {
  return typeof value === "string" && /^[0-9a-fA-F-]{36}$/.test(value);
}

function safeCount(value) {
  const count = Number(value);
  return Number.isFinite(count) ? count : 0;
}

function storyMode(item) {
  if (item.visibility === "private") return "private";
  return item.is_anonymous ? "anonymous" : "public";
}

function storyManagementFriendlyMessage(code) {
  const messages = {
    authentication_required: "Please log in again.",
    story_not_found: "Story not found.",
    forbidden: "You can only manage your own stories.",
    invalid_visibility: "Choose Public, Anonymous, or Private.",
    csrf_failed: "Please refresh the page and try again.",
    invalid_csrf: "Please refresh the page and try again.",
    internal_error: "Something went wrong. Please try again later.",
  };

  return messages[code] || "Something went wrong. Please try again later.";
}

function createStoryManagementControls(item) {
  const storyId = item.story_public_id;
  if (!isPublicUuid(storyId)) return null;

  const controls = document.createElement("div");
  controls.className = "story-owner-controls";
  controls.dataset.storyId = storyId;

  const label = document.createElement("label");
  label.textContent = "Visibility";

  const select = document.createElement("select");
  select.dataset.storyVisibilitySelect = "";

  ["public", "anonymous", "private"].forEach((value) => {
    const option = document.createElement("option");
    option.value = value;
    option.textContent = value.charAt(0).toUpperCase() + value.slice(1);
    option.selected = storyMode(item) === value;
    select.appendChild(option);
  });
  label.appendChild(select);

  const save = document.createElement("button");
  save.type = "button";
  save.className = "btn secondary";
  save.dataset.storyVisibilitySave = "";
  save.textContent = "Save";

  const del = document.createElement("button");
  del.type = "button";
  del.className = "btn secondary";
  del.dataset.storyDelete = "";
  del.textContent = "Delete";

  const msg = document.createElement("p");
  msg.className = "form-message";
  msg.dataset.storyManagementMessage = "";

  controls.append(label, save, del, msg);
  return controls;
}

// Render story cards in the given container
function renderStories(container, items) {
  container.replaceChildren();

  if (!items.length) {
    setStoryListMessage(
      container,
      "You haven't written any stories yet. Let's fix that!"
    );
    return;
  }

  items.forEach((item) => {
    const card = document.createElement("div");
    card.className = "book-card";

    const createdAt = item.created_at
      ? new Date(item.created_at).toLocaleDateString()
      : "";
    const words = safeCount(item.word_count);
    const flowers = safeCount(item.flower_count);
    const storyId = item.story_public_id;
    const hasStoryLink = isPublicUuid(storyId);

    const title = document.createElement(hasStoryLink ? "a" : "span");
    title.className = "book-card-title";
    title.textContent = item.title || "(no title)";

    if (hasStoryLink) {
      title.href = `/story/${encodeURIComponent(storyId)}`;
    }

    const meta = document.createElement("ul");
    meta.className = "book__meta book-card-meta";
    meta.setAttribute("aria-label", "Entry metadata");

    if (item.visibility === "private") {
      const visibilityItem = document.createElement("li");
      visibilityItem.textContent = "Private";
      meta.appendChild(visibilityItem);
    }

    if (item.is_anonymous) {
      const anonymousItem = document.createElement("li");
      anonymousItem.textContent = "Anonymous";
      meta.appendChild(anonymousItem);
    }

    const dateItem = document.createElement("li");
    const time = document.createElement("time");
    time.textContent = createdAt;
    dateItem.appendChild(time);

    const wordsItem = document.createElement("li");
    wordsItem.textContent = `${words} words`;

    const flowersItem = document.createElement("li");
    const flowerIcon = document.createElement("span");
    flowerIcon.setAttribute("aria-hidden", "true");
    flowerIcon.textContent = "\ud83c\udf38";
    flowersItem.append(String(flowers), " ", flowerIcon);

    meta.append(dateItem, wordsItem, flowersItem);
    card.append(title, meta);

    if (isOwnProfile) {
      const controls = createStoryManagementControls(item);
      if (controls) {
        card.appendChild(controls);
      }
    }

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
  const userMain = document.querySelector("div[data-user-public-id]");
  if (!userMain) return;

  const userPid = userMain.dataset.userPublicId;
  isOwnProfile = userMain.dataset.ownProfile === "1";
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

  if (listEl) {
    listEl.addEventListener("click", (e) => handleStoryManagementClick(e, userPid));
  }
}

function setStoryManagementMessage(controls, text, type) {
  const msg = controls?.querySelector("[data-story-management-message]");
  showMsg(msg, text, type);
}

async function handleStoryManagementClick(e, userPid) {
  const saveBtn = e.target.closest("[data-story-visibility-save]");
  const deleteBtn = e.target.closest("[data-story-delete]");
  if (!saveBtn && !deleteBtn) return;

  const controls = e.target.closest(".story-owner-controls");
  const storyPublicId = controls?.dataset.storyId;
  if (!isPublicUuid(storyPublicId)) {
    return;
  }

  if (saveBtn) {
    await saveStoryVisibility(controls, storyPublicId, userPid);
  } else if (deleteBtn) {
    await deleteStory(controls, storyPublicId, userPid);
  }
}

async function saveStoryVisibility(controls, storyPublicId, userPid) {
  const select = controls.querySelector("[data-story-visibility-select]");
  const mode = select?.value || "";
  setStoryManagementMessage(controls, "", null);

  try {
    const formData = new FormData();
    formData.set("mode", mode);

    const res = await fetch(
      `/api/story/${encodeURIComponent(storyPublicId)}/visibility`,
      {
        method: "POST",
        body: formData,
        credentials: "include",
        headers: {
          "X-CSRF-Token": window.CSRF_TOKEN || "",
          Accept: "application/json",
        },
      }
    );

    const data = await res.json();
    if (!res.ok || data.error) {
      return setStoryManagementMessage(
        controls,
        storyManagementFriendlyMessage(data.error),
        "error"
      );
    }

    const item = currentItems.find((story) => story.story_public_id === storyPublicId);
    if (item) {
      item.visibility = data.visibility;
      item.is_anonymous = Boolean(data.is_anonymous);
    }
    renderStories(document.querySelector("#user-stories-list"), currentItems);
    loadUserProfile(userPid);
  } catch (err) {
    console.error("Story visibility error:", err);
    setStoryManagementMessage(
      controls,
      storyManagementFriendlyMessage("internal_error"),
      "error"
    );
  }
}

async function deleteStory(controls, storyPublicId, userPid) {
  if (!confirm("Delete this story permanently?")) {
    return;
  }

  setStoryManagementMessage(controls, "", null);

  try {
    const res = await fetch(`/api/story/${encodeURIComponent(storyPublicId)}`, {
      method: "DELETE",
      credentials: "include",
      headers: {
        "X-CSRF-Token": window.CSRF_TOKEN || "",
        Accept: "application/json",
      },
    });

    const data = await res.json();
    if (!res.ok || data.error) {
      return setStoryManagementMessage(
        controls,
        storyManagementFriendlyMessage(data.error),
        "error"
      );
    }

    currentItems = currentItems.filter((story) => story.story_public_id !== storyPublicId);
    totalStories = Math.max(0, totalStories - 1);
    renderStories(document.querySelector("#user-stories-list"), currentItems);
    updatePaginationArrows();
    loadUserProfile(userPid);
  } catch (err) {
    console.error("Story delete error:", err);
    setStoryManagementMessage(
      controls,
      storyManagementFriendlyMessage("internal_error"),
      "error"
    );
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
      showMsg(msgEl, "Favorite quote saved \u2728", "success");
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
