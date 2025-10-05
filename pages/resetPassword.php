<!-- Reset Password Form -->
<section class="auth-section">
  <div class="container">
    <div class="row justify-content-center align-items-center min-vh-100">
      <div class="col-md-6 col-lg-4">
        <div class="auth-card shadow-lg">
          <div class="text-center mb-4">
            <img src="assets/images/logo-white.png" alt="Resort Logo" class="auth-logo mb-3">
            <p class="text-light">Reset your password</p>
          </div>

          <form id="resetPasswordForm" class="needs-validation" novalidate>
            <input type="hidden" id="resetToken" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">

            <div class="mb-3">
              <label for="newPassword" class="form-label text-light">New Password</label>
              <input type="password" class="form-control" id="newPassword" placeholder="Enter new password" required minlength="6">
              <div class="invalid-feedback">Password must be at least 6 characters.</div>
            </div>

            <div class="mb-3">
              <label for="confirmPassword" class="form-label text-light">Confirm New Password</label>
              <input type="password" class="form-control" id="confirmPassword" placeholder="Confirm new password" required minlength="6">
              <div class="invalid-feedback">Please confirm your password.</div>
            </div>

            <button type="submit" class="btn btn-primary w-100">Reset Password</button>
          </form>

          <hr class="my-4">

          <div class="text-center">
            <p class="mb-0">Remember your password?
              <a href="index.php?page=login" class="fw-bold text-primary">Sign in</a>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<script>
document.getElementById('resetPasswordForm').addEventListener('submit', async function(e) {
  e.preventDefault();

  const newPass = document.getElementById('newPassword').value;
  const confirmPass = document.getElementById('confirmPassword').value;
  const token = document.getElementById('resetToken').value;

  if (newPass !== confirmPass) {
    alert('Passwords do not match!');
    return;
  }

  if (newPass.length < 6) {
    alert('Password must be at least 6 characters.');
    return;
  }

  const submitBtn = e.target.querySelector('button[type="submit"]');
  const originalText = submitBtn.textContent;
  submitBtn.textContent = 'Resetting...';
  submitBtn.disabled = true;

  try {
    const response = await fetch('api/resetPassword.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ token, password: newPass })
    });

    const result = await response.json();

    if (result.success) {
      alert('Password reset successfully! You will be redirected to login.');
      window.location.href = 'index.php?page=login';
    } else {
      alert(result.message || 'Failed to reset password.');
    }
  } catch (error) {
    console.error('Error:', error);
    alert('An error occurred. Please try again.');
  } finally {
    submitBtn.textContent = originalText;
    submitBtn.disabled = false;
  }
});
</script>