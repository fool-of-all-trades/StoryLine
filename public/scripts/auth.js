(async () => {
  // ===== REGISTER FORM FRONT VALIDATION =====
  const registerForm = document.querySelector("#register-form");
  if (registerForm) {
    const regMsg = document.querySelector("#register-message");

    registerForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      // reset message
      if (regMsg) {
        regMsg.textContent = "";
        regMsg.classList.remove("error", "success");
      }

      const form = registerForm;
      const username = (form.username.value || "").trim();
      const pass = form.password.value || "";
      const pass2 = form.password_confirm.value || "";

      //  front validation
      if (username.length < 3)
        return showMsg(
          regMsg,
          "Username must be at least 3 characters long.",
          "error"
        );
      if (pass.length < 8)
        return showMsg(
          regMsg,
          "Password must be at least 8 characters long.",
          "error"
        );
      if (
        !/[a-z]/.test(pass) ||
        !/[A-Z]/.test(pass) ||
        !/\d/.test(pass) ||
        !/[^A-Za-z0-9]/.test(pass)
      )
        return showMsg(
          regMsg,
          "Password must contain a lowercase, uppercase, digit and special character.",
          "error"
        );
      if (pass !== pass2)
        return showMsg(regMsg, "Passwords do not match.", "error");

      try {
        const res = await fetch("/api/register", {
          method: "POST",
          headers: { "Content-Type": "application/x-www-form-urlencoded" },
          body: new URLSearchParams({
            username,
            password: pass,
            password_confirm: pass2,
            csrf: form.csrf?.value || "",
          }),
        });

        const data = await res.json();

        if (!res.ok || data.status === "error") {
          const msg = userFriendlyMessage(data.code || data.error);
          return showMsg(regMsg, msg, "error");
        }

        // success
        showMsg(regMsg, "Registration successful! Redirecting...", "success");
        setTimeout(() => (window.location = "/login"), 1000);
      } catch (err) {
        showMsg(
          regMsg,
          "Something went wrong. Please try again later.",
          "error"
        );
      }
    });
  }

  // ===== LOGIN FORM FRONT VALIDATION =====
  const loginForm = document.querySelector("#login-form");
  if (loginForm) {
    const loginMsg = document.querySelector("#login-message");

    loginForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      if (loginMsg) {
        loginMsg.textContent = "";
        loginMsg.classList.remove("error", "success");
      }

      const form = loginForm;
      const identifier = (form.identifier.value || "").trim();
      const password = form.password.value || "";
      const redirect = form.redirect?.value || "/dashboard";

      if (!identifier || !password) {
        return showMsg(loginMsg, "Podaj login/e-mail i hasło.", "error");
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
            csrf: form.csrf?.value || "",
            redirect,
          }),
          credentials: "include",
        });

        const ctype = res.headers.get("content-type") || "";
        const data = ctype.includes("application/json")
          ? await res.json()
          : null;

        if (!res.ok || (data && (data.status === "error" || data.error))) {
          const code = data?.code || data?.error || "invalid_credentials";
          const msg = userFriendlyMessage(code);
          return showMsg(loginMsg, msg, "error");
        }

        // success
        showMsg(loginMsg, "Zalogowano! Przekierowuję…", "success");
        setTimeout(() => (window.location.href = redirect), 600);
      } catch (err) {
        showMsg(loginMsg, "Coś poszło nie tak. Spróbuj ponownie.", "error");
      }
    });
  }

  // ===== PASSWORD CHANGE FORM FRONT VALIDATION =====
  const passwordChangeForm = document.querySelector("#password-form");
  if (passwordChangeForm) {
    const passwordChangeMsg = document.querySelector("#password-message");

    passwordChangeForm.addEventListener("submit", (e) => {
      e.preventDefault();
      passwordChangeMsg && (passwordChangeMsg.textContent = "");
      passwordChangeMsg &&
        passwordChangeMsg.classList.remove("error", "success");

      const form = passwordChangeForm;
      const pass = form.password.value || "";

      if (pass.length < 8) {
        if (passwordChangeMsg) {
          passwordChangeMsg.textContent =
            "Password must be at least 8 characters long.";
          passwordChangeMsg.classList.add("error");
        }
        return;
      }

      if (
        !/[a-z]/.test(pass) ||
        !/[A-Z]/.test(pass) ||
        !/\d/.test(pass) ||
        !/[^A-Za-z0-9]/.test(pass)
      ) {
        if (passwordChangeMsg) {
          passwordChangeMsg.textContent =
            "Password must contain a lowercase, uppercase, digit and special character.";
          passwordChangeMsg.classList.add("error");
        }
        return;
      }

      passwordChangeForm.submit();
    });
  }

  // ===== USERNAME CHANGE FORM FRONT VALIDATION =====
  const usernameChangeForm = document.querySelector("#username-form");
  if (usernameChangeForm) {
    const usernameChangeMsg = document.querySelector("#username-message");

    usernameChangeForm.addEventListener("submit", (e) => {
      e.preventDefault();
      usernameChangeMsg && (usernameChangeMsg.textContent = "");
      usernameChangeMsg &&
        usernameChangeMsg.classList.remove("error", "success");

      const form = usernameChangeForm;
      const username = form.username.value || "";

      if (username.length < 3) {
        if (usernameChangeMsg) {
          usernameChangeMsg.textContent =
            "Username must be at least 3 characters long.";
          usernameChangeMsg.classList.add("error");
        }
        return;
      }

      usernameChangeForm.submit();
    });
  }

  // ===== FORGOT PASSWORD =====
  const forgotForm = document.querySelector("#forgot-form");
  if (forgotForm) {
    forgotForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg = document.querySelector("#forgot-message");
      msg.textContent = "";

      const fd = new FormData(forgotForm);

      const res = await fetch("/password/forgot", {
        method: "POST",
        body: fd,
        credentials: "include",
      });

      const data = await res.json();

      if (res.ok) {
        msg.textContent = "If this email exists, a reset link was sent.";
        msg.style.color = "green";
      } else {
        msg.textContent = data.error || "Unknown error";
        msg.style.color = "red";
      }
    });
  }

  // ===== RESET PASSWORD =====
  const resetForm = document.querySelector("#reset-form");
  if (resetForm) {
    resetForm.addEventListener("submit", async (e) => {
      e.preventDefault();
      const msg = document.querySelector("#reset-message");
      msg.textContent = "";

      const fd = new FormData(resetForm);

      const res = await fetch("/password/reset", {
        method: "POST",
        body: fd,
        credentials: "include",
      });

      const data = await res.json();

      if (res.ok) {
        msg.style.color = "green";
        msg.textContent = "Password has been updated. You may now log in.";

        setTimeout(() => {
          window.location.href = "/login";
        }, 1500);
      } else {
        msg.style.color = "red";
        msg.textContent = data.error || "Unknown error";
      }
    });
  }

  // ===== TOGGLE PASSWORD VISIBILITY =====
  let togglePasswordVisibilityCheckbox = document.querySelector(
    "#togglePasswordVisibilityCheckbox"
  );

  togglePasswordVisibilityCheckbox?.addEventListener("change", () => {
    var passwordInput = document.querySelector("#passwordInput");
    if (passwordInput.type === "password") {
      passwordInput.type = "text";
    } else {
      passwordInput.type = "password";
    }
  });

  // ===== HELPERS =====
  function showMsg(node, text, type) {
    if (!node) return;
    node.textContent = text || "";
    node.classList.remove("error", "success");
    if (type) node.classList.add(type);
  }
  function userFriendlyMessage(code) {
    switch (code) {
      case "invalid_credentials":
        return "Wrong email or password.";
      case "csrf_failed":
      case "invalid_csrf":
        return "Session expired — refresh and try again.";
      case "rate_limited":
      case "locked":
        return "Too many attempts. Please wait a bit.";
      case "internal_error":
        return "Server error. Please try again later.";
      default:
        return "Something went wrong. Please try again.";
    }
  }
})();
