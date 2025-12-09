function escapeHtml(s) {
  return String(s).replace(
    /[&<>"']/g,
    (c) =>
      ({
        "&": "&amp;",
        "<": "&lt;",
        ">": "&gt;",
        '"': "&quot;",
        "'": "&#039;",
      }[c])
  );
}

function showMsg(node, text, type) {
  if (!node) return;
  node.textContent = text || "";
  node.classList.remove("error", "success");
  if (type) node.classList.add(type);
}

function userFriendlyMessage(code) {
  const messages = {
    invalid_credentials: "Wrong email or password.",
    csrf_failed: "Session expired — refresh and try again.",
    invalid_csrf: "Session expired — refresh and try again.",
    rate_limited: "Too many attempts. Please wait a bit.",
    locked: "Too many attempts. Please wait a bit.",
    internal_error: "Server error. Please try again later.",
  };
  return messages[code] || "Something went wrong. Please try again.";
}
