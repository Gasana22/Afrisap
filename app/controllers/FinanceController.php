<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\ExcelExporter;
use App\Core\PdfExporter;
use App\Core\Validator;
use App\Models\Expense;
use App\Models\Farm;
use App\Models\FinanceReport;
use App\Models\Income;

class FinanceController extends Controller
{
    public function report(): void
    {
        $farmId = $this->input('farm_id') ? (int) $this->input('farm_id') : null;
        $from = $this->input('from') ?: null;
        $to = $this->input('to') ?: null;

        $format = $this->input('format');
        if ($format === 'pdf' || $format === 'excel') {
            $this->exportSummary($farmId, $from, $to, $format);
            return;
        }

        $this->view('finance/report', [
            'pageTitle' => 'Finance',
            'farms' => Farm::all(),
            'selectedFarmId' => $farmId,
            'from' => $from,
            'to' => $to,
            'summary' => FinanceReport::summary($farmId, $from, $to),
            'incomeEntries' => FinanceReport::incomeEntries($farmId, $from, $to),
            'expenseEntries' => FinanceReport::expenseEntries($farmId, $from, $to),
        ]);
    }

    public function incomeIndex(): void
    {
        $this->view('finance/income', ['pageTitle' => 'Income', 'entries' => Income::all(), 'farms' => Farm::all()]);
    }

    public function storeIncome(): void
    {
        $validator = (new Validator($_POST))
            ->required('farm_id', 'Farm')
            ->required('description', 'Description')
            ->required('amount', 'Amount')->numeric('amount', 'Amount')
            ->required('income_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/finance/income');
        }

        $id = Income::create([
            'farm_id' => (int) $this->input('farm_id'),
            'description' => $this->input('description'),
            'amount' => $this->input('amount'),
            'income_date' => $this->input('income_date'),
            'notes' => $this->input('notes'),
        ], Auth::id());

        AuditLogger::log('create', 'income', (string) $id, null, Income::find($id));

        $this->flash('success', 'Income recorded.');
        $this->redirect('/finance/income');
    }

    public function destroyIncome(array $params): void
    {
        $id = (int) $params['id'];
        $before = Income::find($id);
        Income::delete($id);
        AuditLogger::log('delete', 'income', (string) $id, $before, null);

        $this->flash('success', 'Income entry removed.');
        $this->redirect('/finance/income');
    }

    public function expenseIndex(): void
    {
        $this->view('finance/expenses', ['pageTitle' => 'Expenses', 'entries' => Expense::all(), 'farms' => Farm::all()]);
    }

    public function storeExpense(): void
    {
        $validator = (new Validator($_POST))
            ->required('farm_id', 'Farm')
            ->required('category', 'Category')
            ->required('description', 'Description')
            ->required('amount', 'Amount')->numeric('amount', 'Amount')
            ->required('expense_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/finance/expenses');
        }

        $id = Expense::create([
            'farm_id' => (int) $this->input('farm_id'),
            'category' => $this->input('category'),
            'description' => $this->input('description'),
            'amount' => $this->input('amount'),
            'expense_date' => $this->input('expense_date'),
            'notes' => $this->input('notes'),
        ], Auth::id());

        AuditLogger::log('create', 'expenses', (string) $id, null, Expense::find($id));

        $this->flash('success', 'Expense recorded.');
        $this->redirect('/finance/expenses');
    }

    public function destroyExpense(array $params): void
    {
        $id = (int) $params['id'];
        $before = Expense::find($id);
        Expense::delete($id);
        AuditLogger::log('delete', 'expenses', (string) $id, $before, null);

        $this->flash('success', 'Expense entry removed.');
        $this->redirect('/finance/expenses');
    }

    private function exportSummary(?int $farmId, ?string $from, ?string $to, string $format): void
    {
        $income = FinanceReport::incomeEntries($farmId, $from, $to);
        $expenses = FinanceReport::expenseEntries($farmId, $from, $to);

        $headers = ['Type', 'Date', 'Source', 'Farm', 'Amount'];
        $rows = [];
        foreach ($income as $e) {
            $rows[] = ['Income', $e['entry_date'], $e['source'], $e['farm_name'], $e['amount']];
        }
        foreach ($expenses as $e) {
            $rows[] = ['Expense', $e['entry_date'], $e['source'], $e['farm_name'], $e['amount']];
        }
        usort($rows, fn($a, $b) => strcmp((string) $b[1], (string) $a[1]));

        $filenameBase = 'finance-report-' . date('Y-m-d');
        if ($format === 'pdf') {
            PdfExporter::streamTable('Finance Report', $headers, $rows, "{$filenameBase}.pdf");
            return;
        }
        ExcelExporter::streamTable('Finance Report', $headers, $rows, "{$filenameBase}.xlsx");
    }
}
