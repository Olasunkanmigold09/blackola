<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
redirect_if_logged_in();

$error = '';
$email = '';
$flash = flash_get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!csrf_valid($_POST['csrf'] ?? null)) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        try {
            $connection = db();
            $stmt = db_prepare($connection, 'SELECT id, name, password_hash FROM users WHERE email = ?', 's', [$email]);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            $user = mysqli_fetch_assoc($result);
            mysqli_free_result($result);
            mysqli_stmt_close($stmt);

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int) $user['id'];
                $_SESSION['user_name'] = $user['name'];
                header('Location: dashboard.php');
                exit;
            }
            $error = 'Incorrect email or password.';
        } catch (mysqli_sql_exception $ex) {
            error_log($ex->getMessage());
            $error = 'Could not reach the database. Check your settings in config/config.php.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Log in - BLACKOLA CRUD</title>
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
            <h1 class="h3 mb-1 text-center">Welcome back</h1>
            <p class="text-muted text-center mb-4">Log in to see your tasks</p>

            <?php if ($flash): ?>
              <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
              <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">

              <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email"
                       value="<?= e($email) ?>" required autofocus>
              </div>
              <div class="mb-4">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
              </div>

              <button type="submit" class="btn btn-primary w-100">Log in</button>
            </form>

            <p class="text-center mt-4 mb-0">
              New here? <a href="register.php">Create an account</a>
            </p>
          </div>
        </section>
      </div>
    </div>
  </div>
  <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
