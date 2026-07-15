<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$worker = require_worker();
$orgId = (int) $worker['organization_id'];
$workerId = (int) $worker['id'];

$monthParam = (string) ($_GET['month'] ?? date('Y-m'));
if (!preg_match('/^\d{4}-\d{2}$/', $monthParam)) {
    $monthParam = date('Y-m');
}

$firstOfMonth = $monthParam . '-01';
$firstTimestamp = strtotime($firstOfMonth);
$daysInMonth = (int) date('t', $firstTimestamp);
$startWeekday = (int) date('N', $firstTimestamp); // 1 (Mon) - 7 (Sun)
$monthLabel = date('F Y', $firstTimestamp);

$prevMonth = date('Y-m', strtotime('-1 month', $firstTimestamp));
$nextMonth = date('Y-m', strtotime('+1 month', $firstTimestamp));

$monthStart = $monthParam . '-01';
$monthEnd = date('Y-m-t', $firstTimestamp);

$tasks = db_all(
    'SELECT id, title, deadline, status, priority FROM tasks
     WHERE assigned_to = :wid AND organization_id = :org AND deadline BETWEEN :start AND :end
     ORDER BY deadline ASC',
    ['wid' => $workerId, 'org' => $orgId, 'start' => $monthStart, 'end' => $monthEnd]
);

$tasksByDay = [];
foreach ($tasks as $task) {
    $day = (int) date('j', strtotime($task['deadline']));
    $tasksByDay[$day][] = $task;
}

$attendance = db_all(
    'SELECT date, status, clock_in FROM attendance WHERE worker_id = :wid AND date BETWEEN :start AND :end',
    ['wid' => $workerId, 'start' => $monthStart, 'end' => $monthEnd]
);

$attendanceByDay = [];
foreach ($attendance as $a) {
    $attendanceByDay[(int) date('j', strtotime($a['date']))] = $a;
}

$today = date('Y-m-d');

render_header(['title' => 'Calendar', 'context' => 'worker']);
?>
<?php render_worker_topbar($worker); ?>
<div class="worker-content">
    <?php render_alerts(); ?>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= base_url('worker/calendar.php?month=' . $prevMonth) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-left"></i></a>
        <h6 class="fw-bold mb-0"><?= e($monthLabel) ?></h6>
        <a href="<?= base_url('worker/calendar.php?month=' . $nextMonth) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-chevron-right"></i></a>
    </div>

    <div class="content-card p-2 mb-3">
        <table class="table table-borderless table-sm mb-0 text-center" style="table-layout:fixed;">
            <thead>
                <tr class="small text-muted">
                    <th>Mo</th><th>Tu</th><th>We</th><th>Th</th><th>Fr</th><th>Sa</th><th>Su</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $day = 1;
                $cellsInFirstRow = $startWeekday - 1;
                $totalCells = $cellsInFirstRow + $daysInMonth;
                $totalRows = (int) ceil($totalCells / 7);

                for ($row = 0; $row < $totalRows; $row++):
                    echo '<tr>';
                    for ($col = 1; $col <= 7; $col++):
                        $cellIndex = $row * 7 + $col;
                        if ($cellIndex <= $cellsInFirstRow || $day > $daysInMonth) {
                            echo '<td></td>';
                            continue;
                        }

                        $dateStr = sprintf('%s-%02d', $monthParam, $day);
                        $isToday = $dateStr === $today;
                        $dayTasks = $tasksByDay[$day] ?? [];
                        $att = $attendanceByDay[$day] ?? null;

                        $dotClass = '';
                        if ($att) {
                            $dotClass = in_array($att['status'], ['present', 'late', 'half_day'], true) ? 'bg-success' : 'bg-danger';
                        }
                        ?>
                        <td class="align-top" style="height:56px;vertical-align:top;<?= $isToday ? 'background:rgba(13,110,253,.08);border-radius:8px;' : '' ?>">
                            <div class="small fw-<?= $isToday ? 'bold' : 'normal' ?>"><?= $day ?></div>
                            <?php if ($dayTasks): ?>
                                <div class="d-flex justify-content-center flex-wrap gap-1">
                                    <?php foreach (array_slice($dayTasks, 0, 3) as $t): ?>
                                        <span class="badge rounded-pill <?= $t['status'] === 'completed' || $t['status'] === 'verified' ? 'bg-success' : 'bg-primary' ?>" style="width:6px;height:6px;padding:0;" title="<?= e($t['title']) ?>"></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($dotClass): ?>
                                <div class="d-flex justify-content-center mt-1"><span class="rounded-circle <?= $dotClass ?>" style="width:6px;height:6px;display:inline-block;"></span></div>
                            <?php endif; ?>
                        </td>
                        <?php
                        $day++;
                    endfor;
                    echo '</tr>';
                endfor;
                ?>
            </tbody>
        </table>
    </div>

    <h6 class="fw-bold mb-2">Tasks Due This Month</h6>
    <?php if (!$tasks): ?>
        <?php render_empty_state('No tasks with a deadline this month.', 'bi-calendar3'); ?>
    <?php else: ?>
        <?php foreach ($tasks as $task): ?>
            <a href="<?= base_url('worker/task.php?id=' . $task['id']) ?>" class="text-decoration-none text-reset">
                <div class="task-card priority-<?= e($task['priority']) ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="fw-semibold"><?= e($task['title']) ?></div>
                            <div class="small text-muted"><?= e(format_date($task['deadline'])) ?></div>
                        </div>
                        <?php render_status_badge($task['status']); ?>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
<?php render_worker_bottom_nav($_SERVER['SCRIPT_NAME']); ?>
<?php render_footer(['context' => 'worker']); ?>
