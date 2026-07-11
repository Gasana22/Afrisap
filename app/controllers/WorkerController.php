<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\FileUpload;
use App\Core\Notifier;
use App\Core\Validator;
use App\Models\Farm;
use App\Models\Worker;
use App\Models\WorkerAttendance;
use App\Models\WorkerPayroll;
use App\Models\WorkerTask;

class WorkerController extends Controller
{
    public function index(): void
    {
        $this->view('workers/index', ['pageTitle' => 'Workers', 'workers' => Worker::all()]);
    }

    public function create(): void
    {
        $this->view('workers/create', ['pageTitle' => 'Add Worker', 'farms' => Farm::all()]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('farm_id', 'Farm')
            ->required('name', 'Name')
            ->numeric('pay_rate', 'Pay rate');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/workers/create');
        }

        $id = Worker::create([
            'farm_id' => (int) $this->input('farm_id'),
            'name' => $this->input('name'),
            'phone' => $this->input('phone'),
            'role_title' => $this->input('role_title'),
            'hire_date' => $this->input('hire_date'),
            'pay_rate' => $this->input('pay_rate', ''),
            'pay_rate_type' => $this->input('pay_rate_type', 'daily'),
        ]);

        AuditLogger::log('create', 'workers', (string) $id, null, Worker::find($id));

        $this->flash('success', 'Worker added.');
        $this->redirect('/workers/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $worker = Worker::find($id);
        if (!$worker) {
            $this->flash('danger', 'Worker not found.');
            $this->redirect('/workers');
        }

        $this->view('workers/show', [
            'pageTitle' => $worker['name'],
            'worker' => $worker,
            'attendanceRecords' => WorkerAttendance::forWorker($id),
            'tasks' => WorkerTask::forWorker($id),
            'payrollRecords' => WorkerPayroll::forWorker($id),
        ]);
    }

    public function edit(array $params): void
    {
        $worker = Worker::find((int) $params['id']);
        if (!$worker) {
            $this->flash('danger', 'Worker not found.');
            $this->redirect('/workers');
        }

        $this->view('workers/edit', ['pageTitle' => 'Edit ' . $worker['name'], 'worker' => $worker]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = Worker::find($id);
        if (!$before) {
            $this->flash('danger', 'Worker not found.');
            $this->redirect('/workers');
        }

        $validator = (new Validator($_POST))->required('name', 'Name')->numeric('pay_rate', 'Pay rate');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/workers/{$id}/edit");
        }

        Worker::update($id, [
            'name' => $this->input('name'),
            'phone' => $this->input('phone'),
            'role_title' => $this->input('role_title'),
            'hire_date' => $this->input('hire_date'),
            'pay_rate' => $this->input('pay_rate', ''),
            'pay_rate_type' => $this->input('pay_rate_type', 'daily'),
            'user_id' => $before['user_id'],
        ]);

        AuditLogger::log('update', 'workers', (string) $id, $before, Worker::find($id));

        $this->flash('success', 'Worker updated.');
        $this->redirect("/workers/{$id}");
    }

    public function toggleStatus(array $params): void
    {
        $id = (int) $params['id'];
        $before = Worker::find($id);
        if (!$before) {
            $this->redirect('/workers');
        }

        $newStatus = $before['status'] === 'active' ? 'inactive' : 'active';
        Worker::setStatus($id, $newStatus);
        AuditLogger::log($newStatus === 'active' ? 'activate' : 'deactivate', 'workers', (string) $id, $before, ['status' => $newStatus]);

        $this->flash('success', 'Worker status updated.');
        $this->redirect("/workers/{$id}");
    }

    // --- Attendance ---

    public function addAttendance(array $params): void
    {
        $workerId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('attendance_date', 'Date')->required('status', 'Status')
            ->in('status', ['present', 'absent', 'late', 'half_day'], 'Status');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/workers/{$workerId}");
        }

        if (WorkerAttendance::existsForDate($workerId, $this->input('attendance_date'))) {
            $this->flash('danger', 'Attendance for this date is already recorded.');
            $this->redirect("/workers/{$workerId}");
        }

        $photoPath = null;
        try {
            $photoPath = FileUpload::storeImage('photo', 'attendance');
        } catch (\RuntimeException $e) {
            $this->flash('warning', 'Attendance saved, but photo upload failed: ' . $e->getMessage());
        }

        $recordId = WorkerAttendance::create($workerId, [
            'attendance_date' => $this->input('attendance_date'),
            'status' => $this->input('status'),
            'check_in_time' => $this->input('check_in_time'),
            'check_out_time' => $this->input('check_out_time'),
            'gps_lat' => $this->input('gps_lat', ''),
            'gps_lng' => $this->input('gps_lng', ''),
            'notes' => $this->input('notes'),
        ], $photoPath);

        AuditLogger::log('create', 'worker_attendance', (string) $recordId, null, WorkerAttendance::find($recordId));

        $this->flash('success', 'Attendance recorded.');
        $this->redirect("/workers/{$workerId}");
    }

    public function approveAttendance(array $params): void
    {
        $record = WorkerAttendance::find((int) $params['id']);
        if (!$record) {
            $this->redirect('/workers');
        }

        WorkerAttendance::approve((int) $params['id'], Auth::id());
        AuditLogger::log('approve', 'worker_attendance', $params['id'], $record, ['approved_by' => Auth::id()]);

        $this->flash('success', 'Attendance approved.');
        $this->redirect('/workers/' . $record['worker_id']);
    }

    // --- Tasks ---

    public function addTask(array $params): void
    {
        $workerId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('title', 'Title');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/workers/{$workerId}");
        }

        $taskId = WorkerTask::create($workerId, Auth::id(), [
            'title' => $this->input('title'),
            'description' => $this->input('description'),
            'due_date' => $this->input('due_date'),
        ]);
        AuditLogger::log('create', 'worker_tasks', (string) $taskId, null, WorkerTask::find($taskId));

        $worker = Worker::find($workerId);
        if ($worker && $worker['user_id']) {
            Notifier::notify((int) $worker['user_id'], 'New task assigned', $this->input('title'), 'info', "/workers/{$workerId}");
        }

        $this->flash('success', 'Task assigned.');
        $this->redirect("/workers/{$workerId}");
    }

    public function updateTaskStatus(array $params): void
    {
        $task = WorkerTask::find((int) $params['id']);
        if (!$task) {
            $this->redirect('/workers');
        }

        $status = $this->input('status');
        $validator = (new Validator(['status' => $status]))->in('status', ['pending', 'ongoing', 'completed'], 'Status');
        if ($validator->fails()) {
            $this->redirect('/workers/' . $task['worker_id']);
        }

        $photoPath = null;
        try {
            $photoPath = FileUpload::storeImage('photo', 'tasks');
        } catch (\RuntimeException $e) {
            $this->flash('warning', 'Status updated, but photo upload failed: ' . $e->getMessage());
        }

        WorkerTask::updateStatus((int) $params['id'], $status, [
            'gps_lat' => $this->input('gps_lat', ''),
            'gps_lng' => $this->input('gps_lng', ''),
        ], $photoPath);

        AuditLogger::log('update_status', 'worker_tasks', $params['id'], $task, ['status' => $status]);

        $this->flash('success', 'Task status updated.');
        $this->redirect('/workers/' . $task['worker_id']);
    }

    public function verifyTask(array $params): void
    {
        $task = WorkerTask::find((int) $params['id']);
        if (!$task || $task['status'] !== 'completed') {
            $this->flash('danger', 'Only a completed task can be verified.');
            $this->redirect('/workers/' . ($task['worker_id'] ?? ''));
        }

        WorkerTask::verify((int) $params['id'], Auth::id());
        AuditLogger::log('verify', 'worker_tasks', $params['id'], $task, ['verified_by' => Auth::id()]);

        $this->flash('success', 'Task verified.');
        $this->redirect('/workers/' . $task['worker_id']);
    }

    // --- Payroll ---

    public function addPayroll(array $params): void
    {
        $workerId = (int) $params['id'];
        $validator = (new Validator($_POST))
            ->required('period_start', 'Period start')
            ->required('period_end', 'Period end')
            ->required('base_pay', 'Base pay')->numeric('base_pay', 'Base pay')
            ->numeric('bonuses', 'Bonuses')
            ->numeric('deductions', 'Deductions');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/workers/{$workerId}");
        }

        $payrollId = WorkerPayroll::create($workerId, Auth::id(), [
            'period_start' => $this->input('period_start'),
            'period_end' => $this->input('period_end'),
            'base_pay' => $this->input('base_pay'),
            'bonuses' => $this->input('bonuses', ''),
            'deductions' => $this->input('deductions', ''),
        ]);
        AuditLogger::log('create', 'worker_payroll', (string) $payrollId, null, WorkerPayroll::find($payrollId));

        $this->flash('success', 'Payroll record generated.');
        $this->redirect("/workers/{$workerId}");
    }

    public function updatePayrollStatus(array $params): void
    {
        $payroll = WorkerPayroll::find((int) $params['id']);
        if (!$payroll) {
            $this->redirect('/workers');
        }

        $status = $this->input('status');
        $validator = (new Validator(['status' => $status]))->in('status', ['draft', 'approved', 'paid'], 'Status');
        if ($validator->fails()) {
            $this->redirect('/workers/' . $payroll['worker_id']);
        }

        WorkerPayroll::updateStatus((int) $params['id'], $status);
        AuditLogger::log('update_status', 'worker_payroll', $params['id'], $payroll, ['status' => $status]);

        $this->flash('success', 'Payroll status updated.');
        $this->redirect('/workers/' . $payroll['worker_id']);
    }
}
