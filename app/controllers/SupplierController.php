<?php

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Supplier;

class SupplierController extends Controller
{
    public function index(): void
    {
        $this->view('procurement/suppliers/index', ['pageTitle' => 'Suppliers', 'suppliers' => Supplier::all()]);
    }

    public function create(): void
    {
        $this->view('procurement/suppliers/create', ['pageTitle' => 'Add Supplier']);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))->required('name', 'Supplier name');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/suppliers/create');
        }

        $id = Supplier::create([
            'name' => $this->input('name'),
            'contact_person' => $this->input('contact_person'),
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
            'category' => $this->input('category'),
        ]);
        AuditLogger::log('create', 'suppliers', (string) $id, null, Supplier::find($id));

        $this->flash('success', 'Supplier added.');
        $this->redirect('/suppliers');
    }

    public function edit(array $params): void
    {
        $supplier = Supplier::find((int) $params['id']);
        if (!$supplier) {
            $this->flash('danger', 'Supplier not found.');
            $this->redirect('/suppliers');
        }

        $this->view('procurement/suppliers/edit', ['pageTitle' => 'Edit ' . $supplier['name'], 'supplier' => $supplier]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = Supplier::find($id);
        if (!$before) {
            $this->flash('danger', 'Supplier not found.');
            $this->redirect('/suppliers');
        }

        $validator = (new Validator($_POST))->required('name', 'Supplier name');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/suppliers/{$id}/edit");
        }

        Supplier::update($id, [
            'name' => $this->input('name'),
            'contact_person' => $this->input('contact_person'),
            'phone' => $this->input('phone'),
            'email' => $this->input('email'),
            'address' => $this->input('address'),
            'category' => $this->input('category'),
        ]);
        AuditLogger::log('update', 'suppliers', (string) $id, $before, Supplier::find($id));

        $this->flash('success', 'Supplier updated.');
        $this->redirect('/suppliers');
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        if (Supplier::hasPurchaseOrders($id)) {
            $this->flash('danger', 'This supplier has purchase orders on record and cannot be deleted.');
            $this->redirect('/suppliers');
        }

        $before = Supplier::find($id);
        Supplier::delete($id);
        AuditLogger::log('delete', 'suppliers', (string) $id, $before, null);

        $this->flash('success', 'Supplier deleted.');
        $this->redirect('/suppliers');
    }
}
