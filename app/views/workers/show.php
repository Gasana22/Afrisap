<?php use App\Core\Auth; use App\Core\Csrf; $canEdit = Auth::hasPermission('workers.edit'); $isActive = $worker['status'] === 'active'; ?>
<div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
    <div>
        <h4 class="mb-1"><?= htmlspecialchars($worker['name']) ?></h4>
        <p class="text-muted mb-0">
            <?= htmlspecialchars($worker['role_title'] ?? 'Worker') ?> &middot;
            <?= htmlspecialchars($worker['farm_name']) ?>
            <?= $worker['phone'] ? ' &middot; ' . htmlspecialchars($worker['phone']) : '' ?>
            &middot; <span class="badge bg-<?= $worker['status'] === 'active' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($worker['status']) ?></span>
        </p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($canEdit): ?>
        <a href="/workers/<?= (int) $worker['id'] ?>/edit" class="btn btn-outline-secondary btn-sm">Edit</a>
        <?php endif; ?>
        <?php if (Auth::hasPermission('workers.delete')): ?>
        <form method="post" action="/workers/<?= (int) $worker['id'] ?>/toggle-status" onsubmit="return confirm('<?= $worker['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?> this worker?');">
            <?= Csrf::field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm"><?= $worker['status'] === 'active' ? 'Deactivate' : 'Reactivate' ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Pay Rate</div><div class="fs-6 fw-bold"><?= $worker['pay_rate'] !== null ? htmlspecialchars($worker['pay_rate']) . ' / ' . htmlspecialchars($worker['pay_rate_type']) : '—' ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Hire Date</div><div class="fs-6 fw-bold"><?= htmlspecialchars($worker['hire_date'] ?? '—') ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Days Recorded</div><div class="fs-6 fw-bold"><?= count($attendanceRecords) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="card stat-card p-3"><div class="text-muted small">Open Tasks</div><div class="fs-6 fw-bold"><?= count(array_filter($tasks, fn($t) => $t['status'] !== 'verified')) ?></div></div></div>
</div>

<div class="accordion" id="workerAccordion">

    <!-- Attendance -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#secAttendance">Attendance</button></h2>
        <div id="secAttendance" class="accordion-collapse collapse show" data-bs-parent="#workerAccordion">
            <div class="accordion-body">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Date</th><th>Status</th><th>Check-in</th><th>GPS</th><th>Photo</th><th>Check-out</th><th>Approval</th></tr></thead>
                    <tbody>
                        <?php if (empty($attendanceRecords)): ?><tr><td colspan="7" class="text-muted small">No attendance recorded yet.</td></tr><?php endif; ?>
                        <?php foreach ($attendanceRecords as $rec): ?>
                        <tr>
                            <td><?= htmlspecialchars($rec['attendance_date']) ?></td>
                            <td><span class="badge bg-<?= $rec['status'] === 'present' ? 'success' : ($rec['status'] === 'absent' ? 'danger' : 'warning') ?> text-capitalize"><?= htmlspecialchars($rec['status']) ?></span></td>
                            <td><?= htmlspecialchars($rec['check_in_time'] ?? '—') ?></td>
                            <td class="small"><?= $rec['check_in_gps_lat'] ? htmlspecialchars($rec['check_in_gps_lat'] . ', ' . $rec['check_in_gps_lng']) : '—' ?></td>
                            <td><?php if ($rec['check_in_photo']): ?><a href="/<?= htmlspecialchars($rec['check_in_photo']) ?>" target="_blank"><img src="/<?= htmlspecialchars($rec['check_in_photo']) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:4px;" alt="photo"></a><?php endif; ?></td>
                            <td><?= htmlspecialchars($rec['check_out_time'] ?? '—') ?></td>
                            <td>
                                <?php if ($rec['approved_by_name']): ?>
                                <span class="badge bg-info text-dark">Approved by <?= htmlspecialchars($rec['approved_by_name']) ?></span>
                                <?php elseif ($canEdit): ?>
                                <form method="post" action="/attendance/<?= (int) $rec['id'] ?>/approve">
                                    <?= Csrf::field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-success">Approve</button>
                                </form>
                                <?php else: ?>
                                <span class="text-muted small">Pending</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/workers/<?= (int) $worker['id'] ?>/attendance" enctype="multipart/form-data" class="row g-2" id="attendanceForm">
                    <?= Csrf::field() ?>
                    <div class="col-md-2"><input type="date" name="attendance_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                    <div class="col-md-2">
                        <select name="status" class="form-select form-select-sm">
                            <option value="present">Present</option><option value="absent">Absent</option>
                            <option value="late">Late</option><option value="half_day">Half day</option>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="time" name="check_in_time" class="form-control form-control-sm" title="Check-in time"></div>
                    <div class="col-md-2"><input type="time" name="check_out_time" class="form-control form-control-sm" title="Check-out time"></div>
                    <div class="col-md-2"><input type="file" name="photo" accept="image/*" capture="environment" class="form-control form-control-sm"></div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary w-100" id="attendanceGpsBtn"><i class="bi bi-geo-alt"></i> Capture GPS</button>
                    </div>
                    <input type="hidden" name="gps_lat" id="attendanceGpsLat">
                    <input type="hidden" name="gps_lng" id="attendanceGpsLng">
                    <div class="col-md-10"><input type="text" name="notes" class="form-control form-control-sm" placeholder="Notes"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Record</button></div>
                    <div class="col-12"><small class="text-muted" id="attendanceGpsStatus"></small></div>
                </form>
                <script>
                document.getElementById('attendanceGpsBtn')?.addEventListener('click', function () {
                    const status = document.getElementById('attendanceGpsStatus');
                    if (!navigator.geolocation) { status.textContent = 'Geolocation not supported.'; return; }
                    status.textContent = 'Capturing location...';
                    navigator.geolocation.getCurrentPosition(function (pos) {
                        document.getElementById('attendanceGpsLat').value = pos.coords.latitude.toFixed(7);
                        document.getElementById('attendanceGpsLng').value = pos.coords.longitude.toFixed(7);
                        status.textContent = 'GPS captured: ' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5);
                    }, function (err) { status.textContent = 'Could not get location: ' + err.message; });
                });
                </script>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Tasks -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secTasks">Tasks</button></h2>
        <div id="secTasks" class="accordion-collapse collapse" data-bs-parent="#workerAccordion">
            <div class="accordion-body">
                <table class="table table-sm align-middle">
                    <thead><tr><th>Title</th><th>Due</th><th>Assigned By</th><th>GPS / Photo</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($tasks)): ?><tr><td colspan="6" class="text-muted small">No tasks assigned yet.</td></tr><?php endif; ?>
                        <?php foreach ($tasks as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['title']) ?><?php if ($t['description']): ?><div class="text-muted small"><?= htmlspecialchars($t['description']) ?></div><?php endif; ?></td>
                            <td><?= htmlspecialchars($t['due_date'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($t['assigned_by_name']) ?></td>
                            <td class="small">
                                <?= $t['gps_lat'] ? htmlspecialchars($t['gps_lat'] . ', ' . $t['gps_lng']) . '<br>' : '' ?>
                                <?php if ($t['photo_path']): ?><a href="/<?= htmlspecialchars($t['photo_path']) ?>" target="_blank"><img src="/<?= htmlspecialchars($t['photo_path']) ?>" style="width:28px;height:28px;object-fit:cover;border-radius:4px;" alt="photo"></a><?php endif; ?>
                            </td>
                            <td>
                                <?php $taskBadge = ['pending' => 'secondary', 'ongoing' => 'warning', 'completed' => 'info', 'verified' => 'success'][$t['status']] ?? 'secondary'; ?>
                                <span class="badge bg-<?= $taskBadge ?> text-capitalize"><?= htmlspecialchars($t['status']) ?></span>
                                <?php if ($t['verified_by_name']): ?><div class="text-muted small">by <?= htmlspecialchars($t['verified_by_name']) ?></div><?php endif; ?>
                            </td>
                            <td>
                                <?php if ($canEdit && $t['status'] !== 'verified'): ?>
                                    <?php if ($t['status'] === 'pending'): ?>
                                    <form method="post" action="/tasks/<?= (int) $t['id'] ?>/status" class="d-inline"><?= Csrf::field() ?><input type="hidden" name="status" value="ongoing"><input type="hidden" name="gps_lat"><input type="hidden" name="gps_lng"><button class="btn btn-sm btn-outline-warning">Start</button></form>
                                    <?php elseif ($t['status'] === 'ongoing'): ?>
                                    <form method="post" action="/tasks/<?= (int) $t['id'] ?>/status" enctype="multipart/form-data" class="d-inline task-complete-form">
                                        <?= Csrf::field() ?><input type="hidden" name="status" value="completed">
                                        <input type="hidden" name="gps_lat" class="task-gps-lat"><input type="hidden" name="gps_lng" class="task-gps-lng">
                                        <input type="file" name="photo" accept="image/*" capture="environment" class="form-control form-control-sm d-inline w-auto mb-1" style="max-width:140px;">
                                        <button type="button" class="btn btn-sm btn-outline-secondary task-gps-btn">GPS</button>
                                        <button type="submit" class="btn btn-sm btn-outline-info">Complete</button>
                                    </form>
                                    <?php elseif ($t['status'] === 'completed'): ?>
                                    <form method="post" action="/tasks/<?= (int) $t['id'] ?>/verify" class="d-inline"><?= Csrf::field() ?><button class="btn btn-sm btn-outline-success">Verify</button></form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <script>
                document.querySelectorAll('.task-gps-btn').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        const form = btn.closest('form');
                        if (!navigator.geolocation) { alert('Geolocation not supported.'); return; }
                        navigator.geolocation.getCurrentPosition(function (pos) {
                            form.querySelector('.task-gps-lat').value = pos.coords.latitude.toFixed(7);
                            form.querySelector('.task-gps-lng').value = pos.coords.longitude.toFixed(7);
                            btn.textContent = 'GPS ✓';
                        }, function (err) { alert('Could not get location: ' + err.message); });
                    });
                });
                </script>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/workers/<?= (int) $worker['id'] ?>/tasks" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-3"><input type="text" name="title" class="form-control form-control-sm" placeholder="Task title" required></div>
                    <div class="col-md-4"><input type="text" name="description" class="form-control form-control-sm" placeholder="Description"></div>
                    <div class="col-md-3"><input type="date" name="due_date" class="form-control form-control-sm" title="Due date"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Assign</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Payroll -->
    <div class="accordion-item">
        <h2 class="accordion-header"><button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#secPayroll">Payroll</button></h2>
        <div id="secPayroll" class="accordion-collapse collapse" data-bs-parent="#workerAccordion">
            <div class="accordion-body">
                <table class="table table-sm">
                    <thead><tr><th>Period</th><th>Base</th><th>Bonuses</th><th>Deductions</th><th>Net Pay</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php if (empty($payrollRecords)): ?><tr><td colspan="7" class="text-muted small">No payroll records yet.</td></tr><?php endif; ?>
                        <?php foreach ($payrollRecords as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p['period_start']) ?> – <?= htmlspecialchars($p['period_end']) ?></td>
                            <td><?= htmlspecialchars($p['base_pay']) ?></td>
                            <td><?= htmlspecialchars($p['bonuses']) ?></td>
                            <td><?= htmlspecialchars($p['deductions']) ?></td>
                            <td class="fw-bold"><?= htmlspecialchars($p['net_pay']) ?></td>
                            <td><span class="badge bg-<?= ['draft' => 'secondary', 'approved' => 'info', 'paid' => 'success'][$p['status']] ?> text-capitalize"><?= htmlspecialchars($p['status']) ?></span></td>
                            <td>
                                <?php if ($canEdit && $p['status'] === 'draft'): ?>
                                <form method="post" action="/payroll/<?= (int) $p['id'] ?>/status" class="d-inline"><?= Csrf::field() ?><input type="hidden" name="status" value="approved"><button class="btn btn-sm btn-outline-info">Approve</button></form>
                                <?php elseif ($canEdit && $p['status'] === 'approved'): ?>
                                <form method="post" action="/payroll/<?= (int) $p['id'] ?>/status" class="d-inline"><?= Csrf::field() ?><input type="hidden" name="status" value="paid"><button class="btn btn-sm btn-outline-success">Mark Paid</button></form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php if ($canEdit && $isActive): ?>
                <form method="post" action="/workers/<?= (int) $worker['id'] ?>/payroll" class="row g-2">
                    <?= Csrf::field() ?>
                    <div class="col-md-2"><input type="date" name="period_start" class="form-control form-control-sm" required title="Period start"></div>
                    <div class="col-md-2"><input type="date" name="period_end" class="form-control form-control-sm" required title="Period end"></div>
                    <div class="col-md-2"><input type="text" name="base_pay" class="form-control form-control-sm" placeholder="Base pay" required></div>
                    <div class="col-md-2"><input type="text" name="bonuses" class="form-control form-control-sm" placeholder="Bonuses"></div>
                    <div class="col-md-2"><input type="text" name="deductions" class="form-control form-control-sm" placeholder="Deductions"></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-sm btn-outline-success w-100">Generate</button></div>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>
