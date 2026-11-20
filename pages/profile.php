<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login();

$userId = (int) $_SESSION['user_id'];
$profile = [
    'name' => (string) ($_SESSION['user_name'] ?? ''),
    'email' => '',
    'password_hash' => '',
];
$errors = [];
$flash = flash_get();

try {
    $connection = db();
    $profileStmt = db_prepare(
        $connection,
        'SELECT name, email, password_hash FROM users WHERE id = ?',
        'i',
        [$userId]
    );
    mysqli_stmt_execute($profileStmt);
    $profileResult = mysqli_stmt_get_result($profileStmt);
    $user = mysqli_fetch_assoc($profileResult);
    mysqli_free_result($profileResult);
    mysqli_stmt_close($profileStmt);

    if (!$user) {
        header('Location: ../includes/logout.php');
        exit;
    }

    $profile = $user;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_valid($_POST['csrf'] ?? null)) {
            $errors[] = 'Your session expired. Please try again.';
        } else {
            $action = $_POST['action'] ?? '';

            if ($action === 'update_profile') {
                $name = trim((string) ($_POST['name'] ?? ''));
                $email = strtolower(trim((string) ($_POST['email'] ?? '')));

                if ($name === '' || mb_strlen($name) > 100) {
                    $errors[] = 'Please enter your name (up to 100 characters).';
                }
                if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
                    $errors[] = 'Please enter a valid email address.';
                }

                if (!$errors) {
                    $duplicateStmt = db_prepare(
                        $connection,
                        'SELECT id FROM users WHERE email = ? AND id <> ?',
                        'si',
                        [$email, $userId]
                    );
                    mysqli_stmt_execute($duplicateStmt);
                    $duplicateResult = mysqli_stmt_get_result($duplicateStmt);
                    $duplicate = mysqli_fetch_assoc($duplicateResult);
                    mysqli_free_result($duplicateResult);
                    mysqli_stmt_close($duplicateStmt);

                    if ($duplicate) {
                        $errors[] = 'That email address is already in use.';
                    } else {
                        $updateStmt = db_prepare(
                            $connection,
                            'UPDATE users SET name = ?, email = ? WHERE id = ?',
                            'ssi',
                            [$name, $email, $userId]
                        );
                        mysqli_stmt_execute($updateStmt);
                        mysqli_stmt_close($updateStmt);

                        $_SESSION['user_name'] = $name;
                        flash_set('success', 'Your profile has been updated.');
                        header('Location: profile.php');
                        exit;
                    }
                }
            } elseif ($action === 'update_password') {
                $currentPassword = (string) ($_POST['current_password'] ?? '');
                $newPassword = (string) ($_POST['new_password'] ?? '');
                $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

                if ($currentPassword === '' || !password_verify($currentPassword, $profile['password_hash'])) {
                    $errors[] = 'Your current password is incorrect.';
                }
                if (strlen($newPassword) < 8) {
                    $errors[] = 'Your new password must be at least 8 characters.';
                }
                if ($newPassword !== $confirmPassword) {
                    $errors[] = 'The new passwords do not match.';
                }

                if (!$errors) {
                    $updateStmt = db_prepare(
                        $connection,
                        'UPDATE users SET password_hash = ? WHERE id = ?',
                        'si',
                        [password_hash($newPassword, PASSWORD_DEFAULT), $userId]
                    );
                    mysqli_stmt_execute($updateStmt);
                    mysqli_stmt_close($updateStmt);

                    session_regenerate_id(true);
                    flash_set('success', 'Your password has been updated.');
                    header('Location: profile.php');
                    exit;
                }
            } else {
                $errors[] = 'Choose a valid profile action.';
            }

            if ($action === 'update_profile') {
                $profile['name'] = $name;
                $profile['email'] = $email;
            }
        }
    }
} catch (mysqli_sql_exception $ex) {
    error_log($ex->getMessage());
    $errors[] = 'Could not load or update your profile. Please try again.';
}

$initial = mb_strtoupper(mb_substr($profile['name'], 0, 1, 'UTF-8'), 'UTF-8');
$userName = $profile['name'] ?: 'there';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profile - BLACKOLA CRUD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body class="dashboard-page">
  <?php require __DIR__ . '/../includes/header.php'; ?>

  <main class="container workspace-shell profile-shell">
    <header class="workspace-heading">
      <div>
        <p class="eyebrow">ACCOUNT</p>
        <h1>Welcome To Your Profile</h1>
        <p class="workspace-subtitle">Manage your account details and password.</p>
      </div>
    </header>

    <?php if ($flash): ?>
      <div class="alert alert-success profile-alert" role="status"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
      <div class="alert alert-danger profile-alert" role="alert">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $error): ?>
            <li><?= e($error) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <section class="card surface-panel profile-overview" aria-label="Profile summary">
      <div class="profile-avatar" aria-hidden="true"><?= e($initial) ?></div>
      <div>
        <h2 class="profile-name"><?= e($profile['name']) ?></h2>
        <p class="profile-email mb-0"><?= e($profile['email']) ?></p>
      </div>
    </section>

    <div class="row g-4 profile-forms">
      <div class="col-lg-6">
        <section class="card surface-panel h-100">
          <div class="card-body">
            <div class="panel-heading">
              <span class="panel-kicker">PERSONAL DETAILS</span>
              <h2 class="h5 mb-0">Update your profile</h2>
            </div>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="update_profile">
              <div class="mb-3">
                <label for="name" class="form-label">Full name</label>
                <input type="text" class="form-control" id="name" name="name" maxlength="100"
                       value="<?= e($profile['name']) ?>" autocomplete="name" required>
              </div>
              <div class="mb-4">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" maxlength="150"
                       value="<?= e($profile['email']) ?>" autocomplete="email" required>
              </div>
              <button type="submit" class="btn btn-primary add-task-button">Save profile</button>
            </form>
          </div>
        </section>
      </div>

      <div class="col-lg-6">
        <section class="card surface-panel h-100">
          <div class="card-body">
            <div class="panel-heading">
              <span class="panel-kicker">SECURITY</span>
              <h2 class="h5 mb-0">Change password</h2>
            </div>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="update_password">
              <div class="mb-3">
                <label for="currentPassword" class="form-label">Current password</label>
                <input type="password" class="form-control" id="currentPassword" name="current_password"
                       autocomplete="current-password" required>
              </div>
              <div class="mb-3">
                <label for="newPassword" class="form-label">New password</label>
                <input type="password" class="form-control" id="newPassword" name="new_password"
                       minlength="8" autocomplete="new-password" required>
              </div>
              <div class="mb-4">
                <label for="confirmPassword" class="form-label">Confirm new password</label>
                <input type="password" class="form-control" id="confirmPassword" name="confirm_password" minlength="8" autocomplete="new-password" required>
              </div>
              <button type="submit" class="btn btn-primary add-task-button" width="" height="">Update password</button>
            </form>
          </div>
        </section>
       
      </div>
    </div>
   
  </main>
  <a href="./dashboard.php"><button type="submit" class="btn btn-primary add-task-button" width="" height="">BACK TO DASHBOARD</button>
  <?php require __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
