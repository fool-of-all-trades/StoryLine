// ===== REGISTER FORM =====

async function handleRegisterSubmit(e) {
  e.preventDefault();

  const regMsg = document.querySelector("#register-message");
  const form = e.target;

  showMsg(regMsg, "", null);

  const username = (form.username.value || "").trim();
  const email = (form.email.value || "").trim();
  const password = form.password.value || "";
  const passwordConfirm = form.password_confirm.value || "";

  // Front validation
  if (!username || !email || !password || !passwordConfirm) {
    return showMsg(regMsg, "Fill in all required fields.", "error");
  }

  const usernameError = validateUsername(username);
  if (usernameError) return showMsg(regMsg, usernameError, "error");

  const emailError = validateEmail(email);
  if (emailError) return showMsg(regMsg, emailError, "error");

  const passwordError = validatePassword(password);
  if (passwordError) return showMsg(regMsg, passwordError, "error");

  if (password !== passwordConfirm) {
    return showMsg(regMsg, "Passwords do not match.", "error");
  }

  try {
    const res = await fetch("/register", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: new URLSearchParams({
        username,
        email,
        password,
        password_confirm: passwordConfirm,
        csrf: form.csrf?.value || "",
      }),
    });

    const contentType = res.headers.get("content-type") || "";
    const data = contentType.includes("application/json")
      ? await res.json()
      : {};

    if (!res.ok || data.status === "error") {
      const msg = registrationFriendlyMessage(data.code || data.error);
      return showMsg(regMsg, msg, "error");
    }

    showMsg(
      regMsg,
      data.message || "Account created. Please check your email to verify your account.",
      "success"
    );
    setTimeout(() => (window.location = "/login"), 2500);
  } catch (err) {
    showMsg(regMsg, "Something went wrong. Please try again later.", "error");
  }
}

function initRegisterForm() {
  const registerForm = document.querySelector("#register-form");
  if (!registerForm) return;

  registerForm.addEventListener("submit", handleRegisterSubmit);
}

// ===== LOGIN FORM =====

async function handleLoginSubmit(e) {
  e.preventDefault();

  const loginMsg = document.querySelector("#login-message");
  const form = e.target;

  showMsg(loginMsg, "", null);

  const identifier = (form.identifier.value || "").trim();
  const password = form.password.value || "";
  const redirect = form.redirect?.value || "/dashboard";
  const remember = form.remember?.checked ? "1" : "0";

  if (!identifier || !password) {
    return showMsg(loginMsg, "Fill in login/e-mail and password.", "error");
  }

  try {
    const res = await fetch("/login", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        Accept: "application/json",
      },
      body: new URLSearchParams({
        identifier,
        password,
        remember,
        csrf: form.csrf?.value || "",
        redirect,
      }),
      credentials: "include",
    });

    const contentType = res.headers.get("content-type") || "";
    const data = contentType.includes("application/json")
      ? await res.json()
      : null;

    if (!res.ok || (data && (data.status === "error" || data.error))) {
      const code = data?.code || data?.error || "invalid_credentials";
      const msg = loginFriendlyMessage(code);
      return showMsg(loginMsg, msg, "error");
    }

    showMsg(loginMsg, "Success! Redirecting...", "success");
    setTimeout(() => (window.location.href = redirect), 600);
  } catch (err) {
    showMsg(loginMsg, "Something went wrong. Please try again later.", "error");
  }
}

function initLoginForm() {
  const loginForm = document.querySelector("#login-form");
  if (!loginForm) return;

  loginForm.addEventListener("submit", handleLoginSubmit);
}

// ===== PASSWORD CHANGE FORM =====

async function handlePasswordChangeSubmit(e) {
  e.preventDefault();

  const passwordChangeMsg = document.querySelector("#password-message");
  const form = e.target;

  showMsg(passwordChangeMsg, "", null);

  const formData = new FormData(form);
  const currentPassword = String(formData.get("current_password") || "");
  const newPassword = String(formData.get("new_password") || "");
  const passwordConfirm = String(
    formData.get("confirm_password") || formData.get("password_confirm") || ""
  );

  if (!currentPassword) {
    return showMsg(passwordChangeMsg, "Enter your current password.", "error");
  }

  if (!newPassword) {
    return showMsg(passwordChangeMsg, "Enter a new password.", "error");
  }

  if (!passwordConfirm) {
    return showMsg(passwordChangeMsg, "Confirm your new password.", "error");
  }

  const passwordError = validatePassword(newPassword);

  if (passwordError) {
    return showMsg(passwordChangeMsg, passwordError, "error");
  }

  if (newPassword !== passwordConfirm) {
    return showMsg(passwordChangeMsg, "Passwords do not match.", "error");
  }

  try {
    const res = await fetch(form.action || "/api/me/password", {
      method: "POST",
      body: formData,
      credentials: "include",
      headers: {
        "X-CSRF-Token": window.CSRF_TOKEN || "",
        Accept: "application/json",
      },
    });

    const contentType = res.headers.get("content-type") || "";
    const data = contentType.includes("application/json")
      ? await res.json()
      : {};

    if (!res.ok || data.status === "error" || data.error) {
      return showMsg(
        passwordChangeMsg,
        passwordChangeFriendlyMessage(data.error || data.code),
        "error"
      );
    }

    form.reset();
    showMsg(passwordChangeMsg, "Password changed successfully.", "success");
  } catch (err) {
    showMsg(
      passwordChangeMsg,
      "Something went wrong. Please try again later.",
      "error"
    );
  }
}

function initPasswordChangeForm() {
  const passwordChangeForm = document.querySelector("#password-form");
  if (!passwordChangeForm) return;

  passwordChangeForm.addEventListener("submit", handlePasswordChangeSubmit);
}

// ===== ACCOUNT DELETION FORM =====

async function handleAccountDeletionSubmit(e) {
  e.preventDefault();

  const form = e.target;
  const msg = document.querySelector("#delete-account-message");
  showMsg(msg, "", null);

  const formData = new FormData(form);
  const mode = String(formData.get("mode") || "");
  const currentPassword = String(formData.get("current_password") || "");
  const confirmation = String(formData.get("confirmation") || "");

  if (!["delete_all", "orphan_public"].includes(mode)) {
    return showMsg(msg, accountDeletionFriendlyMessage("invalid_delete_mode"), "error");
  }

  if (!currentPassword) {
    return showMsg(msg, accountDeletionFriendlyMessage("current_password_required"), "error");
  }

  if (!confirmation) {
    return showMsg(msg, accountDeletionFriendlyMessage("confirmation_required"), "error");
  }

  try {
    const res = await fetch(form.action || "/api/me/delete-account", {
      method: "POST",
      body: formData,
      credentials: "include",
      headers: {
        "X-CSRF-Token": window.CSRF_TOKEN || "",
        Accept: "application/json",
      },
    });

    const contentType = res.headers.get("content-type") || "";
    const data = contentType.includes("application/json")
      ? await res.json()
      : {};

    if (!res.ok || data.status === "error" || data.error) {
      return showMsg(
        msg,
        accountDeletionFriendlyMessage(data.error || data.code),
        "error"
      );
    }

    showMsg(msg, "Your account has been deleted.", "success");
    setTimeout(() => (window.location.href = "/"), 900);
  } catch (err) {
    showMsg(msg, "Something went wrong. Please try again later.", "error");
  }
}

function initAccountDeletionForm() {
  const form = document.querySelector("#delete-account-form");
  if (!form) return;

  form.addEventListener("submit", handleAccountDeletionSubmit);
}

// ===== USERNAME CHANGE FORM =====

function handleUsernameChangeSubmit(e) {
  e.preventDefault();

  const usernameChangeMsg = document.querySelector("#username-message");
  const form = e.target;

  showMsg(usernameChangeMsg, "", null);

  const username = form.username.value || "";
  const usernameError = validateUsername(username);

  if (usernameError) {
    return showMsg(usernameChangeMsg, usernameError, "error");
  }

  form.submit();
}

function initUsernameChangeForm() {
  const usernameChangeForm = document.querySelector("#username-form");
  if (!usernameChangeForm) return;

  usernameChangeForm.addEventListener("submit", handleUsernameChangeSubmit);
}

// ===== FORGOT PASSWORD =====

async function handleForgotPasswordSubmit(e) {
  e.preventDefault();

  const msg = document.querySelector("#forgot-message");
  const form = e.target;

  msg.textContent = "";

  const formData = new FormData(form);

  try {
    const res = await fetch("/password/forgot", {
      method: "POST",
      body: formData,
      credentials: "include",
    });

    const data = await res.json();

    if (res.ok) {
      msg.textContent = "If this email exists, a reset link was sent.";
      msg.style.color = "green";
    } else {
      msg.textContent = data.message || passwordResetFriendlyMessage(data.error);
      msg.style.color = "red";
    }
  } catch (err) {
    msg.textContent = "Network error. Please try again.";
    msg.style.color = "red";
  }
}

function initForgotPasswordForm() {
  const forgotForm = document.querySelector("#forgot-form");
  if (!forgotForm) return;

  forgotForm.addEventListener("submit", handleForgotPasswordSubmit);
}

// ===== RESET PASSWORD =====

async function handleResetPasswordSubmit(e) {
  e.preventDefault();

  const msg = document.querySelector("#reset-message");
  const form = e.target;

  msg.textContent = "";

  const formData = new FormData(form);
  const selector = String(formData.get("selector") || "");
  const token = String(formData.get("token") || "");
  const password = String(formData.get("password") || "");
  const passwordConfirm = String(formData.get("password_confirm") || "");

  if (!selector || !token) {
    msg.style.color = "red";
    msg.textContent = passwordResetFriendlyMessage("invalid_or_expired_token");
    return;
  }

  const passwordError = validatePassword(password);
  if (passwordError) {
    msg.style.color = "red";
    msg.textContent = passwordResetFriendlyMessage("invalid_password");
    return;
  }

  if (password !== passwordConfirm) {
    msg.style.color = "red";
    msg.textContent = passwordResetFriendlyMessage("password_mismatch");
    return;
  }

  try {
    const res = await fetch("/password/reset", {
      method: "POST",
      body: formData,
      credentials: "include",
    });

    const data = await res.json();

    if (res.ok) {
      msg.style.color = "green";
      msg.textContent = "Password has been updated. You may now log in.";
      setTimeout(() => (window.location.href = "/login"), 1500);
    } else {
      msg.style.color = "red";
      msg.textContent = data.message || passwordResetFriendlyMessage(data.error);
    }
  } catch (err) {
    msg.style.color = "red";
    msg.textContent = "Network error. Please try again.";
  }
}

function initResetPasswordForm() {
  const resetForm = document.querySelector("#reset-form");
  if (!resetForm) return;

  resetForm.addEventListener("submit", handleResetPasswordSubmit);
}

// ===== TOGGLE PASSWORD VISIBILITY =====

function handlePasswordVisibilityToggle(e) {
  const passwordInput = document.querySelector("#passwordInput");
  if (!passwordInput) return;

  passwordInput.type = e.target.checked ? "text" : "password";
}

function initPasswordVisibilityToggle() {
  const toggleCheckbox = document.querySelector(
    "#togglePasswordVisibilityCheckbox"
  );
  if (!toggleCheckbox) return;

  toggleCheckbox.addEventListener("change", handlePasswordVisibilityToggle);
}

// ==== HELPER FUNCTIONS =====
function validatePassword(password) {
  if (password.length < 8) {
    return "Password must be at least 8 characters long.";
  }
  if (
    !/[a-z]/.test(password) ||
    !/[A-Z]/.test(password) ||
    !/\d/.test(password) ||
    !/[^A-Za-z0-9]/.test(password)
  ) {
    return "Password must contain a lowercase, uppercase, digit and special character.";
  }
  return null;
}

function validateEmail(email) {
  if (!email) {
    return "Email is required.";
  }
  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    return "Enter a valid email address.";
  }
  return null;
}

function validateUsername(username) {
  if (username.length < 3) {
    return "Username must be at least 3 characters long.";
  }
  return null;
}

function registrationFriendlyMessage(code) {
  const messages = {
    "Username is already taken": "Username is already taken.",
    username_taken: "Username is already taken.",
    "Email is already in use": "Email is already registered.",
    email_taken: "Email is already registered.",
    "Invalid email address": "Enter a valid email address.",
    invalid_email: "Enter a valid email address.",
    "Invalid username format":
      "Username can use letters, numbers, underscores, and dots.",
    invalid_username:
      "Username can use letters, numbers, underscores, and dots.",
    "Passwords do not match": "Passwords do not match.",
    password_mismatch: "Passwords do not match.",
    password_required: "Password is required.",
    password_too_short: "Password must be at least 8 characters long.",
    password_too_weak:
      "Password must contain a lowercase, uppercase, digit and special character.",
    weak_password:
      "Password must contain a lowercase, uppercase, digit and special character.",
    csrf_failed: "Please refresh the page and try again.",
    invalid_csrf: "Please refresh the page and try again.",
    internal_error: "Something went wrong. Please try again later.",
  };

  return messages[code] || "Something went wrong. Please try again later.";
}

function loginFriendlyMessage(code) {
  const messages = {
    invalid_credentials: "Invalid email or password.",
    bad_credentials: "Invalid email or password.",
    too_many_attempts: "Too many attempts. Please wait a bit.",
    email_not_verified: "Please verify your email before logging in.",
    csrf_failed: "Please refresh the page and try again.",
    invalid_csrf: "Please refresh the page and try again.",
    internal_error: "Something went wrong. Please try again later.",
  };

  return messages[code] || "Something went wrong. Please try again later.";
}

function passwordChangeFriendlyMessage(code) {
  const messages = {
    authentication_required: "Please log in again to change your password.",
    invalid_current_password: "Current password is incorrect.",
    invalid_password:
      "New password must contain a lowercase, uppercase, digit and special character.",
    password_mismatch: "Passwords do not match.",
    too_many_requests: "Too many attempts. Please wait a bit.",
    csrf_failed: "Please refresh the page and try again.",
    invalid_csrf: "Please refresh the page and try again.",
    internal_error: "Something went wrong. Please try again later.",
  };

  return messages[code] || "Something went wrong. Please try again later.";
}

function accountDeletionFriendlyMessage(code) {
  const messages = {
    authentication_required: "Please log in again before deleting your account.",
    current_password_required: "Enter your current password.",
    invalid_current_password: "Current password is incorrect.",
    invalid_delete_mode: "Choose how your stories should be handled.",
    confirmation_required: "Type the required confirmation phrase exactly.",
    cannot_delete_last_admin: "This is the last admin account and cannot be deleted.",
    too_many_requests: "Too many attempts. Please wait a bit.",
    csrf_failed: "Please refresh the page and try again.",
    invalid_csrf: "Please refresh the page and try again.",
    internal_error: "Something went wrong. Please try again later.",
  };

  return messages[code] || "Something went wrong. Please try again later.";
}

function passwordResetFriendlyMessage(code) {
  const messages = {
    email_required: "Enter your email address.",
    invalid_or_expired_token: "This reset link is invalid or has expired.",
    invalid_password:
      "Password is too weak. Use at least 8 characters, including an uppercase letter, a number, and a special character.",
    password_required: "Enter a new password.",
    password_too_short:
      "Password is too weak. Use at least 8 characters, including an uppercase letter, a number, and a special character.",
    password_too_weak:
      "Password is too weak. Use at least 8 characters, including an uppercase letter, a number, and a special character.",
    password_too_long: "Password is too long.",
    password_mismatch: "Passwords do not match.",
    too_many_requests: "Too many attempts. Please wait a bit.",
    csrf_failed: "Please refresh the page and try again.",
    invalid_csrf: "Please refresh the page and try again.",
    internal_error: "Something went wrong. Please try again later.",
  };

  return messages[code] || "Something went wrong. Please try again later.";
}

// ===== INITIALIZATION =====
initRegisterForm();
initLoginForm();
initPasswordChangeForm();
initAccountDeletionForm();
initUsernameChangeForm();
initForgotPasswordForm();
initResetPasswordForm();
initPasswordVisibilityToggle();
