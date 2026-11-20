(function () {
  'use strict';

  var csrf = document.querySelector('meta[name="csrf-token"]').content;
  var currentFilter = 'all';
  var allTasks = [];
  var toastTimer;

  var listEl = document.getElementById('taskList');
  var emptyEl = document.getElementById('emptyState');
  var formEl = document.getElementById('taskForm');
  var alertEl = document.getElementById('formAlert');
  var searchEl = document.getElementById('taskSearch');
  var toastEl = document.getElementById('toastMessage');
  var reminderPanel = document.getElementById('reminderPanel');
  var reminderList = document.getElementById('reminderList');

  function escapeHtml(str) {
    var div = document.createElement('div');
    div.textContent = str == null ? '' : String(str);
    return div.innerHTML;
  }

  function api(body) {
    return fetch('../pages/tasks.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
      body: JSON.stringify(body)
    }).then(function (res) {
      return res.json().then(function (data) {
        if (!res.ok) { throw new Error(data.error || 'Something went wrong.'); }
        return data;
      });
    });
  }

  function showError(msg) {
    alertEl.textContent = msg;
    alertEl.classList.remove('d-none');
  }

  function clearError() {
    alertEl.classList.add('d-none');
  }

  function showToast(message) {
    toastEl.textContent = message;
    toastEl.classList.add('is-visible');
    window.clearTimeout(toastTimer);
    toastTimer = window.setTimeout(function () {
      toastEl.classList.remove('is-visible');
    }, 2400);
  }

  function priorityBadge(p) {
    var cls = { low: 'priority-low', medium: 'priority-medium', high: 'priority-high' }[p] || 'priority-low';
    return '<span class="priority-badge ' + cls + '">' + escapeHtml(p) + '</span>';
  }

  function dueLabel(task) {
    if (!task.due_date) { return ''; }
    var overdue = !task.is_done && daysUntil(task.due_date) < 0;
    return '<span class="task-due' + (overdue ? ' overdue' : '') + '">' +
      'Due ' + escapeHtml(task.due_date) + (overdue ? ' (overdue)' : '') + '</span>';
  }

  function visibleTasks() {
    var query = searchEl.value.trim().toLowerCase();
    return allTasks.filter(function (task) {
      var matchesFilter = currentFilter === 'all' ||
        (currentFilter === 'pending' && !task.is_done) ||
        (currentFilter === 'completed' && task.is_done);
      var searchable = [task.title, task.description, task.priority].join(' ').toLowerCase();
      return matchesFilter && (!query || searchable.indexOf(query) !== -1);
    });
  }

  function render() {
    var tasks = visibleTasks();
    listEl.innerHTML = '';
    listEl.setAttribute('aria-busy', 'false');
    emptyEl.classList.toggle('d-none', tasks.length > 0);

    if (!tasks.length) {
      var query = searchEl.value.trim();
      var emptyTitle = document.getElementById('emptyTitle');
      var emptyMessage = document.getElementById('emptyMessage');
      if (query) {
        emptyTitle.textContent = 'No matches';
        emptyMessage.textContent = 'Try a different search term.';
      } else if (allTasks.length) {
        emptyTitle.textContent = 'Nothing in this view';
        emptyMessage.textContent = 'Choose another filter to see more tasks.';
      } else {
        emptyTitle.textContent = 'Nothing here yet';
        emptyMessage.textContent = 'Add a task to get started.';
      }
    }

    tasks.forEach(function (t) {
      var item = document.createElement('div');
      item.className = 'list-group-item task-item d-flex align-items-start gap-3';
      item.innerHTML =
        '<input class="form-check-input mt-1" type="checkbox" data-toggle="' + t.id + '"' +
          (t.is_done ? ' checked' : '') + ' aria-label="Mark ' + (t.is_done ? 'incomplete' : 'complete') + '">' +
        '<div class="flex-grow-1 task-content">' +
          '<div class="task-title' + (t.is_done ? ' done' : '') + ' fw-semibold">' +
            escapeHtml(t.title) + '</div>' +
          (t.description ? '<div class="task-description">' + escapeHtml(t.description) + '</div>' : '') +
          '<div class="task-meta">' + priorityBadge(t.priority) + dueLabel(t) + '</div>' +
        '</div>' +
        '<button class="delete-task" type="button" data-delete="' + t.id + '" aria-label="Delete task">Delete</button>';
      listEl.appendChild(item);
    });
  }

  function animateNumber(element, targetValue) {
    var startValue = Number(element.textContent) || 0;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
      element.textContent = targetValue;
      return;
    }
    var startTime = null;
    function step(timestamp) {
      if (startTime === null) { startTime = timestamp; }
      var progress = Math.min((timestamp - startTime) / 380, 1);
      var eased = 1 - Math.pow(1 - progress, 3);
      element.textContent = Math.round(startValue + (targetValue - startValue) * eased);
      if (progress < 1) { window.requestAnimationFrame(step); }
    }
    window.requestAnimationFrame(step);
  }

  function updateStats(s) {
    animateNumber(document.getElementById('statTotal'), s.total);
    animateNumber(document.getElementById('statPending'), s.pending);
    animateNumber(document.getElementById('statDone'), s.done);
    var percent = s.total ? Math.round((s.done / s.total) * 100) : 0;
    var progress = document.querySelector('.completion-track');
    document.getElementById('completionPercent').textContent = percent + '%';
    document.getElementById('completionCaption').textContent = s.total
      ? s.done + ' of ' + s.total + ' tasks complete'
      : 'No tasks completed yet';
    document.getElementById('completionBar').style.width = percent + '%';
    progress.setAttribute('aria-valuenow', percent);
  }

  function daysUntil(dateString) {
    var parts = dateString.split('-').map(Number);
    if (parts.length !== 3 || parts.some(function (part) { return !part; })) { return null; }
    var dueDay = Date.UTC(parts[0], parts[1] - 1, parts[2]);
    var now = new Date();
    var today = Date.UTC(now.getFullYear(), now.getMonth(), now.getDate());
    return Math.round((dueDay - today) / 86400000);
  }

  function renderReminders() {
    var upcoming = allTasks.filter(function (task) {
      return !task.is_done && task.due_date && daysUntil(task.due_date) <= 7;
    }).sort(function (a, b) {
      return (daysUntil(a.due_date) || 0) - (daysUntil(b.due_date) || 0);
    });
    reminderPanel.classList.toggle('d-none', upcoming.length === 0);
    reminderList.innerHTML = upcoming.slice(0, 4).map(function (task) {
      var days = daysUntil(task.due_date);
      var dueLabel = days < 0 ? 'Overdue by ' + Math.abs(days) + (Math.abs(days) === 1 ? ' day' : ' days')
        : days === 0 ? 'Due today'
        : days === 1 ? 'Due tomorrow'
        : 'Due in ' + days + ' days';
      return '<li class="reminder-item' + (days <= 0 ? ' urgent' : '') + '">' +
        '<span class="reminder-task-title">' + escapeHtml(task.title) + '</span>' +
        '<span class="reminder-date">' + dueLabel + '</span>' +
        '</li>';
    }).join('');
    if (upcoming.length > 4) {
      reminderList.insertAdjacentHTML('beforeend', '<li class="reminder-overflow">And ' + (upcoming.length - 4) + ' more</li>');
    }
  }

  function load() {
    listEl.setAttribute('aria-busy', 'true');
    listEl.innerHTML = '<div class="loading-state">Loading tasks</div>';
    clearError();
    fetch('../pages/tasks.php?filter=all')
      .then(function (res) {
        if (res.status === 401) { window.location = 'login.php'; throw new Error('Logged out'); }
        if (!res.ok) { throw new Error('Could not load tasks.'); }
        return res.json();
      })
      .then(function (data) {
        allTasks = data.tasks;
        render();
        renderReminders();
        updateStats(data.stats);
      })
      .catch(function (err) {
        if (err.message !== 'Logged out') {
          listEl.innerHTML = '';
          listEl.setAttribute('aria-busy', 'false');
          showError('Could not load tasks. Please try again.');
        }
      });
  }

  // Add a task
  formEl.addEventListener('submit', function (e) {
    e.preventDefault();
    clearError();
    var submitButton = formEl.querySelector('[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Adding...';
    api({
      action: 'create',
      title: document.getElementById('title').value,
      description: document.getElementById('description').value,
      priority: document.getElementById('priority').value,
      due_date: document.getElementById('due_date').value
    }).then(function () {
      formEl.reset();
      document.getElementById('priority').value = 'medium';
      showToast('Task added');
      load();
    }).catch(function (err) {
      showError(err.message);
    }).then(function () {
      submitButton.disabled = false;
      submitButton.innerHTML = 'Add task <span aria-hidden="true">+</span>';
    });
  });

  // Toggle / delete (event delegation)
  listEl.addEventListener('change', function (e) {
    var id = e.target.getAttribute('data-toggle');
    if (id) {
      api({ action: 'toggle', id: Number(id) }).then(function () {
        showToast(e.target.checked ? 'Task completed' : 'Task reopened');
        load();
      }).catch(function (err) { showError(err.message); });
    }
  });

  listEl.addEventListener('click', function (e) {
    var deleteButton = e.target.closest('[data-delete]');
    var id = deleteButton && deleteButton.getAttribute('data-delete');
    if (id && window.confirm('Delete this task?')) {
      api({ action: 'delete', id: Number(id) }).then(function () {
        showToast('Task deleted');
        load();
      }).catch(function (err) { showError(err.message); });
    }
  });

  // Filter buttons
  document.querySelectorAll('[data-filter]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      document.querySelectorAll('[data-filter]').forEach(function (b) { b.classList.remove('active'); });
      btn.classList.add('active');
      document.querySelectorAll('[data-filter]').forEach(function (b) {
        b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
      });
      currentFilter = btn.getAttribute('data-filter');
      render();
    });
  });

  searchEl.addEventListener('input', render);
  document.getElementById('todayLabel').textContent = new Intl.DateTimeFormat(undefined, {
    weekday: 'long', month: 'long', day: 'numeric'
  }).format(new Date()).toUpperCase();

  window.setInterval(renderReminders, 60000);
  load();
})();
