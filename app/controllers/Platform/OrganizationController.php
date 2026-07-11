<?php

namespace App\Controllers\Platform;

use App\Core\AuditLogger;
use App\Core\Validator;
use App\Models\Organization;
use App\Models\User;

class OrganizationController extends PlatformController
{
    public function index(): void
    {
        $this->view('platform/organizations/index', [
            'pageTitle' => 'Organizations',
            'organizations' => Organization::all(),
        ]);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $organization = Organization::find($id);
        if (!$organization) {
            $this->flash('danger', 'Organization not found.');
            $this->redirect('/platform/organizations');
        }

        $this->view('platform/organizations/show', [
            'pageTitle' => $organization['name'],
            'organization' => $organization,
            'members' => User::forOrganization($id),
        ]);
    }

    public function toggleStatus(array $params): void
    {
        $id = (int) $params['id'];
        $before = Organization::find($id);
        if (!$before) {
            $this->redirect('/platform/organizations');
        }

        $validator = (new Validator($_POST))->required('status', 'Status')->in('status', ['active', 'suspended'], 'Status');
        if ($validator->fails()) {
            $this->redirect("/platform/organizations/{$id}");
        }

        $newStatus = $this->input('status');
        Organization::setStatus($id, $newStatus);
        AuditLogger::log($newStatus === 'suspended' ? 'suspend' : 'reactivate', 'organizations', (string) $id, $before, ['status' => $newStatus]);

        $this->flash('success', "Organization {$newStatus}.");
        $this->redirect("/platform/organizations/{$id}");
    }
}
