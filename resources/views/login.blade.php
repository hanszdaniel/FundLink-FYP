<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Fundlink Login</title>
  <link rel="stylesheet" href="{{ asset('style.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="page-login">
  <div class="container auth-frame">

    <!-- LEFT SIDE -->
    <div class="left">
      <div class="logo">
        <img src="logo.PNG" alt="Fundlink Logo">
      </div>
      <div class="illustration">
        <img src="illustration.png" alt="Illustration">
      </div>
      <h2>Complete Transparency.</h2>
      <h1>Shared Prosperity.</h1>
      <p>Track every dollar, save collaboratively, and spend together, wisely.</p>
    </div>

    <!-- RIGHT SIDE -->
    <div class="right">

      <div class="top-buttons">
        <button type="button" class="signup-btn" onclick="window.location.href='{{ url('/signup') }}'">Sign up</button>
        <button type="button" class="login-btn" onclick="window.location.href='{{ url('/login') }}'">Sign in</button>
      </div>

      <h2 class="welcome">Welcome back, <span>User</span></h2>

      <!-- LOGIN FORM -->
      <form id="loginForm" class="form">
        @csrf
        <label>Email address</label>
        <input type="email" id="email" placeholder="hello@example.com" required>

        <label>Password</label>
        <input type="password" id="password" placeholder="********" required>

        <a href="#" class="forgot">Forgot Password?</a>

        <button type="submit" class="login-btn">
          Login <i class="fas fa-arrow-right"></i>
        </button>
      </form>

      <p id="message" style="margin-top:15px; font-weight:bold;"></p>
    </div>

    <!-- FOOTER -->
    <div class="footer-bar">
      <div class="footer-links">
        <a href="#">Terms and conditions</a>
        <span aria-hidden="true">|</span>
        <a href="#">FAQs</a>
        <span aria-hidden="true">|</span>
        <a href="#">Contact us</a>
      </div>
      <div class="socials">
        <a href="#"><i class="fab fa-facebook-f"></i></a>
        <a href="#"><i class="fab fa-twitter"></i></a>
        <a href="#"><i class="fab fa-linkedin-in"></i></a>
        <a href="#"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
  </div>

  <!-- ✨ FORGOT PASSWORD MODAL (UPDATED) -->
  <div id="forgotModal" class="modal-overlay" aria-hidden="true">
    <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="forgot-title">

      <div class="modal-header">
        <h3 id="forgot-title">Trouble logging in?</h3>
        <button type="button" class="modal-close forgot-close" aria-label="Close">&times;</button>
      </div>

      <p class="modal-subtext">
        Enter your email and we'll send you a reset link to get back into your account.
      </p>

      <!-- IMPORTANT: FORM POINTS TO BACKEND -->
      <form id="forgotForm" class="modal-form" method="POST" action="{{ route('password.email.custom') }}">
        @csrf

        <label>Email address</label>
        <input 
          type="email" 
          id="forgot-contact" 
          name="email" 
          placeholder="Email address"
          required
        >

        <p id="forgot-message" class="modal-message"></p>

        <div class="modal-actions">
          <button type="button" class="btn-ghost forgot-cancel">Cancel</button>
          <button type="submit" class="btn-primary">Send</button>
        </div>
      </form>

    </div>
  </div>

  <!-- 💡 JAVASCRIPT HANDLING -->
  <script>
  document.addEventListener("DOMContentLoaded", () => {

      /* -----------------------------
         LOGIN FORM (AJAX)
      ----------------------------- */
      const loginForm = document.querySelector("#loginForm");
      const message = document.querySelector("#message");

      loginForm.addEventListener("submit", async (e) => {
          e.preventDefault();

          const email = document.querySelector("#email").value.trim();
          const password = document.querySelector("#password").value.trim();

          try {
              const response = await fetch("{{ url('/login') }}", {
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json",
                      "Accept": "application/json",
                      "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content
                  },
                  credentials: "same-origin",
                  body: JSON.stringify({ email, password })
              });

              const result = await response.json();
              message.textContent = result.message;
              message.style.color = (response.ok && result.status === "success") ? "green" : "red";

              if (response.ok && result.status === "success") {
                  setTimeout(() => window.location.href = "{{ url('/dashboard') }}", 900);
              }
          } catch (error) {
              message.textContent = "Error connecting to server.";
              message.style.color = "red";
          }
      });

      /* -----------------------------
         FORGOT PASSWORD MODAL
      ----------------------------- */
      const forgotLink = document.querySelector(".forgot");
      const forgotModal = document.querySelector("#forgotModal");
      const forgotForm = document.querySelector("#forgotForm");
      const forgotInput = document.querySelector("#forgot-contact");
      const forgotMessage = document.querySelector("#forgot-message");

      const openForgot = () => {
          forgotModal.classList.add("open");
          forgotModal.setAttribute("aria-hidden", "false");
          forgotInput.focus();
      };

      const closeForgot = () => {
          forgotModal.classList.remove("open");
          forgotModal.setAttribute("aria-hidden", "true");
          forgotForm.reset();
          forgotMessage.textContent = "";
      };

      forgotLink.addEventListener("click", (e) => {
          e.preventDefault();
          openForgot();
      });

      document.querySelectorAll(".forgot-close, .forgot-cancel").forEach(btn =>
          btn.addEventListener("click", closeForgot)
      );

      forgotModal.addEventListener("click", (e) => {
          if (e.target === forgotModal) closeForgot();
      });

      /* -----------------------------
         FORGOT PASSWORD SUBMIT
      ----------------------------- */
      forgotForm.addEventListener("submit", async (e) => {
          e.preventDefault();

          const email = forgotInput.value.trim();

          if (!email) {
              forgotMessage.textContent = "Please enter your email.";
              forgotMessage.style.color = "red";
              return;
          }

          try {
              const response = await fetch("{{ route('password.email.custom') }}", {
                  method: "POST",
                  headers: {
                      "Content-Type": "application/json",
                      "Accept": "application/json",
                      "X-CSRF-TOKEN": document.querySelector('meta[name=\"csrf-token\"]').content,
                      "X-Requested-With": "XMLHttpRequest"
                  },
                  credentials: "include",
                  body: JSON.stringify({ email })
              });

              const contentType = response.headers.get("content-type") || "";
              const text = await response.text();
              const isHtmlResponse = contentType.includes("text/html");

              // Redirects or HTML responses (e.g., when already logged in) should not dump markup on the page.
              if (isHtmlResponse || response.redirected) {
                  const fallback = response.ok
                      ? "If that email is registered, we'll email you a reset link."
                      : "Unable to process reset right now. Please try again.";
                  forgotMessage.textContent = fallback;
                  forgotMessage.style.color = response.ok ? "green" : "red";
                  return;
              }

              let result = {};
              try { result = JSON.parse(text); } catch (_) { result = {}; }

              if (response.ok) {
                  forgotMessage.textContent = result.message || "A reset link has been sent to your email.";
                  forgotMessage.style.color = "green";
              } else {
                  // Laravel validation 422 returns errors.email
                  const validationMsg = result?.errors?.email?.[0];
                  let fallback = "Email not found.";
                  if (response.status === 419) fallback = "Session expired. Please refresh and try again.";
                  forgotMessage.textContent = validationMsg || result.message || fallback;
                  forgotMessage.style.color = "red";
              }

          } catch (err) {
              forgotMessage.textContent = "Error sending request.";
              forgotMessage.style.color = "red";
          }
      });

  });
  </script>

</body>
</html>
