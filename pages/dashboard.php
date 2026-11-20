<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
require_login();

$userName = $_SESSION['user_name'] ?? 'there';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
  <title>Tasks - BLACKOLA CRUD</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="../assets/style.css" rel="stylesheet">
</head>
<body class="dashboard-page">

  <?php require __DIR__ . '/../includes/header.php'; ?>

  <main class="container workspace-shell">
    <header class="workspace-heading">
      <div>
        <p class="eyebrow" id="todayLabel">YOUR WORKSPACE</p>
        <h1>Good to see you, <?= e($userName) ?>.</h1>
        <p class="workspace-subtitle">Here’s your day at a glance.</p>
      </div>
      <div class="completion-panel" aria-live="polite">
        <div class="d-flex justify-content-between align-items-baseline gap-3">
          <span class="completion-label">Completion</span>
          <strong id="completionPercent">0%</strong>
        </div>
        <div class="progress completion-track" role="progressbar" aria-label="Tasks completed" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
          <div class="progress-bar" id="completionBar" style="width: 0%"></div>
        </div>
        <span class="completion-caption" id="completionCaption">No tasks completed yet</span>
      </div>
    </header>

    <section class="reminder-panel d-none" id="reminderPanel" aria-label="Due-date reminders" aria-live="polite">
      <div class="reminder-heading">
        <span class="reminder-mark" aria-hidden="true">!</span>
        <div>
          <span class="panel-kicker">COMING UP</span>
          <h2>Due-date reminders</h2>
        </div>
      </div>
      <ul class="reminder-list" id="reminderList"></ul>
    </section>

    <!-- Stats -->
    <section class="row g-3 stats-row" aria-label="Task statistics">
      <div class="col-4">
        <div class="card stat-card stat-total h-100">
          <div class="card-body">
            <span class="stat-label">Total tasks</span>
            <div class="stat-number" id="statTotal">0</div>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="card stat-card stat-pending h-100">
          <div class="card-body">
            <span class="stat-label">In progress</span>
            <div class="stat-number" id="statPending">0</div>
          </div>
        </div>
      </div>
      <div class="col-4">
        <div class="card stat-card stat-done h-100">
          <div class="card-body">
            <span class="stat-label">Completed</span>
            <div class="stat-number" id="statDone">0</div>
          </div>
        </div>
      </div>
    </section>

    <div class="row g-4 workspace-grid">
      <!-- New task form -->
      <div class="col-lg-4">
        <section class="card surface-panel h-100">
          <div class="card-body">
            <div class="panel-heading">
              <span class="panel-kicker">MAKE A NOTE</span>
              <h2 class="h5 mb-0">New task</h2>
            </div>
            <div id="formAlert" class="alert alert-danger d-none" role="alert"></div>
            <form id="taskForm" novalidate>
              <div class="mb-3">
                <label for="title" class="form-label">Title</label>
                <input type="text" class="form-control" id="title" maxlength="200" required>
              </div>
              <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" rows="3"></textarea>
              </div>
              <div class="row g-3 mb-3">
                <div class="col-6">
                  <label for="priority" class="form-label">Priority</label>
                  <select class="form-select" id="priority">
                    <option value="low">Low</option>
                    <option value="medium" selected>Medium</option>
                    <option value="high">High</option>
                  </select>
                </div>
                <div class="col-6">
                  <label for="due_date" class="form-label">Due date</label>
                  <input type="date" class="form-control" id="due_date">
                </div>
              </div>
              <button type="submit" class="btn btn-primary w-100 add-task-button">Add task <span aria-hidden="true">+</span></button>
            </form>
          </div>
        </section>
      </div>
      <div class="col-lg-8">
        <section class="card surface-panel task-panel h-100">
          <div class="card-body">
            <div class="task-panel-heading">
              <div>
                <span class="panel-kicker">YOUR LIST</span>
                <h2 class="h5 mb-0">Tasks</h2>
              </div>
              <label class="search-wrap">
                <span class="visually-hidden">Search tasks</span>
                <input type="search" class="form-control" id="taskSearch" placeholder="Search tasks" autocomplete="off">
              </label>
            </div>
            <div class="filter-row">
              <div class="btn-group task-filters" role="group" aria-label="Filter tasks">
                <button type="button" class="btn active" data-filter="all" aria-pressed="true">All</button>
                <button type="button" class="btn" data-filter="pending" aria-pressed="false">In progress</button>
                <button type="button" class="btn" data-filter="completed" aria-pressed="false">Completed</button>
              </div>
            </div>
            <div id="taskList" class="list-group list-group-flush" aria-live="polite" aria-busy="true"></div>
            <div id="emptyState" class="empty-state d-none">
              <span class="empty-mark" aria-hidden="true">—</span>
              <p class="empty-title" id="emptyTitle">Nothing here yet</p>
              <p class="empty-copy mb-0" id="emptyMessage">Add a task to get started.</p>
            </div>
          </div>
        </section>
      </div>
    </div>
  </main>

  <div id="toastMessage" class="task-toast" role="status" aria-live="polite"></div>
  <?php require __DIR__ . '/../includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="../assets/app.js"></script>
</body>
</html>
