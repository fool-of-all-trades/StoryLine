// Fetch and display today's quote
async function loadTodaysQuote() {
  const quoteEl = document.querySelector("[data-quote]");
  if (!quoteEl) return;

  // get today's quote
  let response = await fetch("/api/quote/today", { credentials: "include" });

  // fallback if not found in db, create it
  if (response.status === 404) {
    await fetch("/api/quote/today", {
      method: "POST",
      credentials: "include",
      headers: { "X-CSRF-Token": window.CSRF_TOKEN },
    });
    response = await fetch("/api/quote/today", { credentials: "include" });
  }

  const quote = await response.json();

  document.querySelector("[data-date]").textContent =
    quote.date || new Date().toISOString().slice(0, 10);
  document.querySelector("[data-quote]").textContent = `"${quote.sentence}"`;
  document.querySelector("[data-meta]").textContent =
    [quote.source_book, quote.source_author].filter(Boolean).join(" — ") || "—";
}

// Count words in a string
function wordCount(text) {
  const words = text.match(/\p{L}[\p{L}\p{N}''-]*/gu);
  return words ? words.length : 0;
}

// Handle story form submission
async function handleStorySubmit(e) {
  e.preventDefault();

  const storyForm = e.target;
  const storyMsg = document.querySelector("#story-message");
  const storyTextarea = document.querySelector("#story-textarea");
  const guestNameInput = document.querySelector("#guest-name");

  // Clear previous messages
  if (storyMsg) {
    storyMsg.textContent = "";
    storyMsg.classList.remove("error", "success");
  }

  const content = (storyTextarea?.value || "").trim();
  const wordLimit = parseInt(storyTextarea?.dataset.wordlimit || "500", 10);

  // Validate content
  if (!content) {
    if (storyMsg) {
      storyMsg.textContent = "Your story can't be empty.";
      storyMsg.classList.add("error");
    }
    return;
  }

  // Validate word limit
  const words = content.split(/\s+/).filter(Boolean);
  if (words.length > wordLimit) {
    if (storyMsg) {
      storyMsg.textContent = `Your story is too long (${words.length}/${wordLimit} words).`;
      storyMsg.classList.add("error");
    }
    return;
  }

  // Validate guest name
  if (guestNameInput) {
    const guestName = guestNameInput.value.trim();
    if (guestName.length > 60) {
      if (storyMsg) {
        storyMsg.textContent = "Your name is too long (max 60 characters).";
        storyMsg.classList.add("error");
      }
      return;
    }
  }

  // Submit form
  const formData = new FormData(storyForm);

  try {
    const res = await fetch("/api/story", {
      method: "POST",
      body: formData,
      credentials: "include",
      headers: { "X-CSRF-Token": window.CSRF_TOKEN },
    });

    const data = await res.json();

    if (res.ok) {
      // redirect to the list of today's stories
      location.href = "/stories?date=today&sort=new";
    } else {
      if (storyMsg) {
        storyMsg.textContent =
          data.error || "Something went wrong while saving your story.";
        storyMsg.classList.add("error");
      } else {
        alert("Error: " + (data.error || "unknown"));
      }
    }
  } catch (err) {
    if (storyMsg) {
      storyMsg.textContent =
        "Network error while saving your story. Please try again.";
      storyMsg.classList.add("error");
    } else {
      alert("Network error");
    }
  }
}

// Initialize word counter
function initWordCounter() {
  const storyTextarea = document.querySelector("#story-textarea");
  const counterEl = document.querySelector("#word-count-span");

  if (!storyTextarea || !counterEl) return;

  storyTextarea.addEventListener("input", () => {
    const currentContent = storyTextarea.value.trim();
    const count = wordCount(currentContent);
    counterEl.textContent = count;
  });
}

// Initialize autosave functionality
function initAutosave() {
  const storyTextarea = document.querySelector("#story-textarea");
  const storyForm = document.querySelector("#story-form");

  if (!storyTextarea || !storyForm) return;

  const challengeId = storyTextarea.dataset.challengeId || "unknown";
  const KEY = `storyline:draft:${challengeId}`;

  // Restore draft from localStorage
  if (!storyTextarea.value) {
    const draft = localStorage.getItem(KEY);
    if (draft) storyTextarea.value = draft;
  }

  // Debounced autosave
  let saveTimer = null;
  storyTextarea.addEventListener("input", () => {
    clearTimeout(saveTimer);
    saveTimer = setTimeout(() => {
      try {
        localStorage.setItem(KEY, storyTextarea.value);
      } catch (e) {
        // storage is full? well that's rough buddy
        console.warn("Autosave failed:", e);
      }
    }, 800);
  });

  // After the user submits the form, clear the saved draft
  storyForm.addEventListener("submit", () => localStorage.removeItem(KEY));
}

// Initialize story form
function initStoryForm() {
  const storyForm = document.querySelector("#story-form");
  if (!storyForm) return;

  storyForm.addEventListener("submit", handleStorySubmit);
}

// Initialize all features
loadTodaysQuote();
initStoryForm();
initWordCounter();
initAutosave();

// session keep-alive only if the user is actively typing
// let lastTyping = Date.now();
// storyTextarea.addEventListener("input", () => {
//   lastTyping = Date.now();
// });

// setInterval(() => {
//   // if the user typed in the last minute, ping the server in 5 minutes to keep the session alive
//   if (Date.now() - lastTyping < 60_000) {
//     fetch("/auth/ping", {
//       method: "POST",
//       headers: {
//         "X-CSRF-Token": window.CSRF_TOKEN || "",
//       },
//     }).catch(() => {});
//   }
// }, 300_000);
