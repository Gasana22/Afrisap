<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\FileUpload;
use App\Core\Validator;
use App\Models\Farm;
use App\Models\MediaFile;

class MediaController extends Controller
{
    public function index(): void
    {
        $category = $this->input('category') ?: null;
        $farmId = $this->input('farm_id') ? (int) $this->input('farm_id') : null;

        $this->view('media/index', [
            'pageTitle' => 'Media & Documents',
            'files' => MediaFile::all($category, $farmId),
            'farms' => Farm::all(),
            'selectedCategory' => $category,
            'selectedFarmId' => $farmId,
        ]);
    }

    public function create(): void
    {
        $this->view('media/create', ['pageTitle' => 'Upload File', 'farms' => Farm::all()]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))->required('title', 'Title')->required('category', 'Category');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/media/create');
        }

        try {
            $filePath = FileUpload::storeImage('file', 'media');
        } catch (\RuntimeException $e) {
            $this->flash('danger', 'Upload failed: ' . $e->getMessage());
            $this->redirect('/media/create');
        }

        if (!$filePath) {
            $this->flash('danger', 'Please choose a file to upload.');
            $this->redirect('/media/create');
        }

        $id = MediaFile::create([
            'farm_id' => $this->input('farm_id'),
            'category' => $this->input('category'),
            'title' => $this->input('title'),
            'notes' => $this->input('notes'),
        ], $filePath, Auth::id());

        AuditLogger::log('create', 'media_files', (string) $id, null, MediaFile::find($id));

        $this->flash('success', 'File uploaded.');
        $this->redirect('/media');
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $before = MediaFile::find($id);
        if (!$before) {
            $this->redirect('/media');
        }

        MediaFile::delete($id);
        AuditLogger::log('delete', 'media_files', (string) $id, $before, null);

        $this->flash('success', 'File deleted.');
        $this->redirect('/media');
    }
}
