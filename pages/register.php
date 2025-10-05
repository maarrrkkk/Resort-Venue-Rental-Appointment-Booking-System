<!-- Registration Form -->
<section class="auth-section">
  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-6 col-lg-5">
        <div class="auth-card shadow-lg">
          <div class="text-center mb-4">
            <img src="assets/images/logo-white.png" alt="Resort Logo" class="auth-logo mb-3">
            <h2 class="fw-bold text-light">Create Account</h2>
            <p class="text-light">Join us to start booking amazing venues</p>
          </div>

          <form id="registerForm" class="needs-validation" novalidate>
            <!-- Full Name -->
            <div class="mb-3">
              <label for="name" class="form-label text-light">Full Name</label>
              <input 
                type="text" 
                class="form-control" 
                id="name" 
                placeholder="Enter your full name" 
                required>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label for="email" class="form-label text-light">Email</label>
              <input 
                type="email" 
                class="form-control" 
                id="email" 
                placeholder="Enter your email" 
                required>
            </div>

            <!-- Phone Number -->
            <div class="mb-3">
              <label for="phone" class="form-label text-light">Phone Number</label>
              <input 
                type="text" 
                class="form-control" 
                id="phone" 
                placeholder="+63-9XX-XXX-XXXX" 
                required
                pattern="^[0-9]{11}$"
                maxlength="11"
                inputmode="numeric"
                title="Phone number must be exactly 11 digits and numbers only">
              <div class="invalid-feedback">
                Phone number must be exactly 11 digits and numbers only.
              </div>
            </div>

            <!-- Password -->
            <div class="mb-3">
              <label for="password" class="form-label text-light">Password</label>
              <input 
                type="password" 
                class="form-control" 
                id="password" 
                placeholder="Create a password" 
                required
                pattern="^(?=.*[A-Z])[A-Za-z0-9]{11,}$"
                title="Password must be at least 11 characters, include at least 1 uppercase letter, and contain only letters and numbers">
              <div class="invalid-feedback">
                Password must be at least 11 characters, include 1 uppercase, and only letters/numbers.
              </div>
            </div>

            <!-- Confirm Password -->
            <div class="mb-3">
              <label for="confirmPassword" class="form-label text-light">Confirm Password</label>
              <input 
                type="password" 
                class="form-control" 
                id="confirmPassword" 
                placeholder="Confirm your password" 
                required
                pattern="^(?=.*[A-Z])[A-Za-z0-9]{11,}$"
                title="Confirm password must follow the same rules as password">
              <div class="invalid-feedback">
                Passwords must match and follow the password rules.
              </div>
            </div>

            <!-- Terms -->
            <div class="form-check mb-3">
              <input type="checkbox" class="form-check-input" id="agreeTerms" required>
              <label class="form-check-label" for="agreeTerms">
                I agree to the <a href="#" class="text-light">Terms</a> & <a href="#" class="text-light">Privacy Policy</a>
              </label>
            </div>

            <button type="submit" class="btn btn-primary w-100">Create Account</button>
           </form>

           <!-- Verification Step -->
           <div id="verificationStep" class="d-none">
             <hr class="my-4">
             <h3 class="text-center text-light mb-4">Verify Your Email</h3>
             <p class="text-center text-light mb-4" id="verificationMessage">Verification code sent to your email. Please check your inbox and enter the code below.</p>
             <form id="verifyForm">
               <div class="mb-3">
                 <label for="verificationCode" class="form-label text-light">Verification Code</label>
                 <input type="text" class="form-control" id="verificationCode" placeholder="Enter 6-digit code" maxlength="6" required>
               </div>
               <button type="submit" class="btn btn-primary w-100">Verify Email</button>
             </form>
             <div class="text-center mt-3">
               <button id="resendCode" class="btn btn-link text-light">Resend Code</button>
             </div>
           </div>

           <hr class="my-4">

           <div class="text-center">
             <p class="mb-0">Already have an account?
               <a href="index.php?page=login" class="fw-bold text-primary">Sign in</a>
             </p>
           </div>
        </div>
      </div>
    </div>
  </div>
</section>
