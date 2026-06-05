<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>FundLink - Create Account</title>
  
  <link rel="stylesheet" href="{{ asset('style.css') }}">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body class="page-signup">
  <div class="container">

    <!-- Left Side -->
    <div class="left">
      <div class="logo">
        <img src="logo.png" alt="FundLink Logo" onerror="this.src='https://placehold.co/150x50/333/FFF?text=Fundlink'">
      </div>
      <div class="illustration">
        <img src="illustration.png" alt="Growing wealth illustration" onerror="this.src='https://placehold.co/400x300/EEE/AAA?text=Illustration'">
      </div>
      <h2>Complete Transparency.</h2>
      <h1>Shared Prosperity.</h1>
      <p>Track every dollar, save collaboratively, and spend together, wisely.</p>
    </div>
    
    <!-- Right Side -->
    <div class="right">
      <div class="top-buttons">
        <button type="button" class="signup-btn" onclick="window.location.href='{{ url('/signup') }}'">Sign Up</button>
        <button type="button" class="login-btn" onclick="window.location.href='{{ url('/login') }}'">Sign In</button>
      </div>
      
      <div class="section-header"> 
        <h2 class="section-title">Create an Account</h2>
        <a href="{{ url('/login') }}" class="section-subtle-link">Log in instead</a>
      </div>

      <!-- THIS FORM IS NOW CORRECT -->
      <form id="signupForm" class="form" style="margin-top: 30px;">
        <label for="name">Name</label>
        <input type="text" id="name" required>

        <label for="email">Email</label>
        <input type="email" id="email" required>
        
        <!-- CHANGED 'phone' to 'phone_number' to match the controller -->
        <label for="phone_number">Phone Number</label>
        <input type="text" id="phone_number" required>

        <label for="password">Password</label>
        <input type="password" id="password" required>
        
        <!-- Password Confirmation (required by controller) -->
        <label for="password_confirmation">Confirm Password</label>
        <input type="password" id="password_confirmation" required>

        <button type="submit" class="login-btn">Create an Account</button>
      </form>
      
      <p id="message" style="margin-top:15px; color:#4B4DED; font-weight:bold;"></p>
    </div>

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

 <!-- THIS SCRIPT IS NOW CORRECT -->
 <script>
  document.getElementById("signupForm").addEventListener("submit", async (e) => {
      e.preventDefault();

      const message = document.getElementById("message");

      // 1. Get values from the corrected form fields
      const name = document.getElementById("name").value.trim();
      const email = document.getElementById("email").value.trim();
      // CHANGED 'phone' to 'phone_number'
      const phone_number = document.getElementById("phone_number").value.trim();
      const password = document.getElementById("password").value.trim();
      const password_confirmation = document.getElementById("password_confirmation").value.trim();

      // 2. Simple validation
      // CHANGED 'phone' to 'phone_number'
      if (!name || !email || !phone_number || !password || !password_confirmation) {
          message.style.color = "red";
          message.textContent = "Please fill all fields.";
          return;
      }

      if (password !== password_confirmation) {
          message.style.color = "red";
          message.textContent = "Passwords do not match.";
          return;
      }

      try {
          // 3. Post to the correct Laravel route
          const response = await fetch("{{ url('/signup') }}", {
              method: "POST",
              headers: { 
                  "Content-Type": "application/json",
                  "Accept": "application/json",
                  // 4. Send the required CSRF token
                  "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
              },
              // 5. Send the correct JSON payload
              // CHANGED 'phone' to 'phone_number'
              body: JSON.stringify({ 
                  name, 
                  email,
                  phone_number, // <-- This now matches the controller
                  password, 
                  password_confirmation 
              })
          });

          const result = await response.json();

          if (response.ok) { // Status 200-299
              message.style.color = "green";
              message.textContent = "Account created successfully! Redirecting...";
              setTimeout(() => {
                  window.location.href = "{{ url('/dashboard') }}";
              }, 1000);
          } else { // Status 422 (Validation) or other error
              message.style.color = "red";
              if (result.errors) {
                  let errorMsg = result.message + "\n";
                  for (const key in result.errors) {
                      errorMsg += `- ${result.errors[key][0]}\n`;
                  }
                  message.innerText = errorMsg; // Use .innerText to show line breaks
              } else {
                message.textContent = result.message || "An unknown error occurred.";
              }
          }
      } catch (error) {
          console.error("Fetch error:", error);
          message.textContent = "Something went wrong. Please try again.";
          message.style.color = "red";
      }
  });
</script>

</body>
</html>
