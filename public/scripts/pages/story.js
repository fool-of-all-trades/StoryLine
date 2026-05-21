const storyFull = document.querySelector(".story-full");

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

function setManagementMessage(controls, text, type) {
  const msg = controls?.querySelector("[data-story-management-message]");
  if (typeof showMsg === "function") {
    showMsg(msg, text, type);
    return;
  }
  if (msg) {
    msg.textContent = text || "";
  }
}

async function handleStoryLike(e) {
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
      const countElement = storyFull.querySelector("[data-count]");
      countElement.textContent = data.count;
    } else if (
      data?.error === "authentication_required" ||
      data?.error === "unauthorized"
    ) {
      location.href = "/login";
    } else {
      alert(`Error: ${data.error || "unknown"}`);
    }
  } catch (error) {
    console.error("Failed to update story:", error);
    alert("An unexpected error occurred");
  }
}

async function handleVisibilitySave(e) {
  const btn = e.target.closest("[data-story-visibility-save]");
  if (!btn) return;

  const controls = btn.closest("[data-owner-story-controls]");
  const storyPublicId = controls?.dataset.storyId;
  const select = controls?.querySelector("[data-story-visibility-select]");
  const mode = select?.value || "";

  setManagementMessage(controls, "", null);

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
      return setManagementMessage(
        controls,
        storyManagementFriendlyMessage(data.error),
        "error"
      );
    }

    setManagementMessage(controls, "Visibility updated.", "success");
    setTimeout(() => window.location.reload(), 500);
  } catch (error) {
    console.error("Failed to update story visibility:", error);
    setManagementMessage(
      controls,
      storyManagementFriendlyMessage("internal_error"),
      "error"
    );
  }
}

async function handleStoryDelete(e) {
  const btn = e.target.closest("[data-story-delete]");
  if (!btn) return;

  const controls = btn.closest("[data-owner-story-controls]");
  const storyPublicId = controls?.dataset.storyId;
  const ownerPublicId = controls?.dataset.ownerPublicId;

  if (!confirm("Delete this story permanently?")) {
    return;
  }

  setManagementMessage(controls, "", null);

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
      return setManagementMessage(
        controls,
        storyManagementFriendlyMessage(data.error),
        "error"
      );
    }

    window.location.href = ownerPublicId ? `/user/${encodeURIComponent(ownerPublicId)}` : "/stories/today";
  } catch (error) {
    console.error("Failed to delete story:", error);
    setManagementMessage(
      controls,
      storyManagementFriendlyMessage("internal_error"),
      "error"
    );
  }
}

function initStoryLikes() {
  if (!storyFull) return;
  storyFull.addEventListener("click", handleStoryLike);
  storyFull.addEventListener("click", handleVisibilitySave);
  storyFull.addEventListener("click", handleStoryDelete);
}

initStoryLikes();
