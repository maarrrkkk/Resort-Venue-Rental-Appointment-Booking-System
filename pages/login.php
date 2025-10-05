<!-- Login Form -->
<section class="auth-section">
  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-6 col-lg-4">
        <div class="auth-card shadow-lg">
          <div class="text-center mb-4">
            <img src="assets/images/logo-white.png" alt="Resort Logo" class="auth-logo mb-3">
            <p class="text-light">Sign in to manage your bookings</p>
          </div>

          <form id="loginForm" class="needs-validation" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label text-light">Email</label>
              <input type="email" class="form-control" id="email" placeholder="Enter your email" required>
              <div class="invalid-feedback">Please provide a valid email.</div>
            </div>

            <div class="mb-3">
              <label for="password" class="form-label text-light">Password</label>
              <input type="password" class="form-control" id="password" placeholder="Enter your password" required>
              <div class="invalid-feedback">Please provide a password.</div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
               <div class="form-check">
                 <input type="checkbox" class="form-check-input" id="rememberMe">
                 <label class="form-check-label" for="rememberMe">Remember me</label>
               </div>
               <a href="#" class="text-primary small" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal">Forgot password?</a>
             </div>

            <button type="submit" class="btn btn-primary w-100">Sign In</button>
          </form>

          <hr class="my-4">

          <div class="text-center">
            <p class="mb-0">Don’t have an account?
              <a href="index.php?page=register" class="fw-bold text-primary">Sign up</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- Forgot Password Modal -->
<div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="forgotPasswordModalLabel">Forgot Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form id="forgotPasswordForm">
          <div class="mb-3">
            <label for="forgotEmail" class="form-label">Email address</label>
            <input type="email" class="form-control" id="forgotEmail" placeholder="Enter your email" required>
            <div class="invalid-feedback">Please provide a valid email.</div>
          </div>
          <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
        </form>
      </div>
    </div>
  </div>
</div>
