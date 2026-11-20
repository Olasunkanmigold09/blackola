<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
redirect_if_logged_in();

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired. Please try again.';
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm'] ?? '';

    if ($name === '' || mb_strlen($name) > 100) {
        $errors[] = 'Please enter your name (up to 100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors) {
        try {
            $connection = db();
            $stmt = db_prepare($connection, 'SELECT id FROM users WHERE email = ?', 's', [$email]);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $existingUser = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);

            if ($existingUser) {
                $errors[] = 'An account with that email already exists.';
            } else {
                $insert = db_prepare(
                    $connection,
                    'INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)',
                    'sss',
                    [$name, $email, password_hash($password, PASSWORD_DEFAULT)]
                );
                mysqli_stmt_execute($insert);
                mysqli_stmt_close($insert);
                flash_set('success', 'Account created. You can now log in.');
                header('Location: login.php');
                exit;
            }
        } catch (mysqli_sql_exception $ex) {
            error_log($ex->getMessage());
            $errors[] = 'Could not reach the database. Check your settings in config/config.php.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register - BLACKOLA CRUD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body class="auth-bg auth-page">
  <div class="container auth-content">
    <div class="row align-items-center auth-row">
      <div class="col-lg-6 auth-brand-column">
  <section class="auth-brand-panel">
  <a class="auth-brand" href="../index.php">
    <span class="brand-mark" aria-hidden="true">B</span>
    <span>BLACKOLA CRUD</span>
  </a>
  <div class="auth-brand-copy">
    <p class="auth-brand-kicker">A CLEARER KIND OF BUSY</p>
    <h2>Make room for what matters.</h2>
    <p>Bring your next steps into focus and keep your day moving with intention.</p>
  </div>
  <div class="auth-brand-graphic" aria-hidden="true">
    <div class="auth-graphic-heading">
      <span>WEEKLY RHYTHM</span>
      <span>01 / 03</span>
    </div>
    <div class="auth-graphic-lines">
      <div class="auth-graphic-line is-complete"><span>01</span><i></i><b>PLAN</b></div>
      <div class="auth-graphic-line is-current"><span>02</span><i></i><b>FOCUS</b></div>
      <div class="auth-graphic-line"><span>03</span><i></i><b>FINISH</b></div>
    </div>
  </div>
  <p class="auth-brand-signoff">PERSONAL WORKSPACE <span>EST. 2026</span></p>
  </section>
      </div>
      <div class="col-lg-6 auth-form-column">
        <section class="auth-card">
          <div class="auth-card-body">
            <h1 class="h3 mb-1 text-center">Create your account</h1>
            <p class="text-muted text-center mb-4">Start organising your tasks with BLACKOLA CRUD</p>

            <?php if ($errors): ?>
              <div class="alert alert-danger">
                <ul class="mb-0 ps-3">
                  <?php foreach ($errors as $err): ?>
                    <li><?= e($err) ?></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="post" class="auth-form">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

              <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="name" name="name"
                       value="<?= e($name) ?>" required>
              </div>
              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e($email) ?>" required>
              </div>
              <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password"
                       minlength="8" required>
                <div class="form-text">At least 8 characters.</div>
              </div>
              <div class="mb-4">
                <label for="confirm" class="form-label">Confirm password</label>
                <input type="password" class="form-control" id="confirm" name="confirm" required>
              </div>

              <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>

            <p class="text-center mt-4 mb-0">
              Already have an account? <a href="login.php">Log in</a>
            </p>
          </div>
        </section>
      </div>
    </div>
  </div>
  <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
