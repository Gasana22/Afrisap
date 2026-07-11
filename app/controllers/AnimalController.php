<?php

namespace App\Controllers;

use App\Core\AuditLogger;
use App\Core\Controller;
use App\Core\Validator;
use App\Models\Animal;
use App\Models\AnimalBreeding;
use App\Models\AnimalFeeding;
use App\Models\AnimalMortality;
use App\Models\AnimalProduction;
use App\Models\AnimalSale;
use App\Models\AnimalTreatment;
use App\Models\AnimalVaccination;
use App\Models\AnimalWeight;
use App\Models\Farm;

class AnimalController extends Controller
{
    public function index(): void
    {
        $this->view('livestock/index', ['pageTitle' => 'Livestock', 'animals' => Animal::all()]);
    }

    public function create(): void
    {
        $this->view('livestock/create', [
            'pageTitle' => 'Add Animal',
            'farms' => Farm::all(),
            'existingAnimals' => Animal::all(),
        ]);
    }

    public function store(): void
    {
        $validator = (new Validator($_POST))
            ->required('farm_id', 'Farm')
            ->required('species', 'Species')
            ->required('gender', 'Gender')
            ->in('gender', ['male', 'female'], 'Gender');

        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect('/livestock/create');
        }

        $id = Animal::create([
            'farm_id' => (int) $this->input('farm_id'),
            'name' => $this->input('name'),
            'species' => $this->input('species'),
            'breed' => $this->input('breed'),
            'tag_number' => $this->input('tag_number'),
            'gender' => $this->input('gender'),
            'birth_date' => $this->input('birth_date'),
            'parent_id' => $this->input('parent_id') ?: null,
        ]);

        AuditLogger::log('create', 'animals', (string) $id, null, Animal::find($id));

        $this->flash('success', 'Animal registered with ID ' . Animal::find($id)['animal_code'] . '.');
        $this->redirect('/livestock/' . $id);
    }

    public function show(array $params): void
    {
        $id = (int) $params['id'];
        $animal = Animal::find($id);
        if (!$animal) {
            $this->flash('danger', 'Animal not found.');
            $this->redirect('/livestock');
        }

        $this->view('livestock/show', [
            'pageTitle' => $animal['animal_code'],
            'animal' => $animal,
            'vaccinations' => AnimalVaccination::forAnimal($id),
            'feedings' => AnimalFeeding::forAnimal($id),
            'weights' => AnimalWeight::forAnimal($id),
            'treatments' => AnimalTreatment::forAnimal($id),
            'breedingRecords' => AnimalBreeding::forAnimal($id),
            'productionRecords' => AnimalProduction::forAnimal($id),
            'mortality' => AnimalMortality::forAnimal($id),
            'sale' => AnimalSale::forAnimal($id),
        ]);
    }

    public function edit(array $params): void
    {
        $animal = Animal::find((int) $params['id']);
        if (!$animal) {
            $this->flash('danger', 'Animal not found.');
            $this->redirect('/livestock');
        }

        $this->view('livestock/edit', [
            'pageTitle' => 'Edit ' . $animal['animal_code'],
            'animal' => $animal,
            'existingAnimals' => array_filter(Animal::all(), fn($a) => (int) $a['id'] !== (int) $animal['id']),
        ]);
    }

    public function update(array $params): void
    {
        $id = (int) $params['id'];
        $before = Animal::find($id);
        if (!$before) {
            $this->flash('danger', 'Animal not found.');
            $this->redirect('/livestock');
        }

        $validator = (new Validator($_POST))->required('gender', 'Gender')->in('gender', ['male', 'female'], 'Gender');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$id}/edit");
        }

        Animal::update($id, [
            'name' => $this->input('name'),
            'breed' => $this->input('breed'),
            'tag_number' => $this->input('tag_number'),
            'gender' => $this->input('gender'),
            'birth_date' => $this->input('birth_date'),
            'parent_id' => $this->input('parent_id') ?: null,
        ]);

        AuditLogger::log('update', 'animals', (string) $id, $before, Animal::find($id));

        $this->flash('success', 'Animal profile updated.');
        $this->redirect("/livestock/{$id}");
    }

    public function destroy(array $params): void
    {
        $id = (int) $params['id'];
        $animal = Animal::find($id);
        if (!$animal) {
            $this->redirect('/livestock');
        }

        if (Animal::hasAnyHistory($id)) {
            $this->flash('danger', 'This animal has recorded history and cannot be deleted (traceability requires permanent history). Deactivate it instead by recording a sale or mortality.');
            $this->redirect("/livestock/{$id}");
        }

        Animal::delete($id);
        AuditLogger::log('delete', 'animals', (string) $id, $animal, null);

        $this->flash('success', 'Animal profile deleted.');
        $this->redirect('/livestock');
    }

    // --- Vaccinations ---

    public function addVaccination(array $params): void
    {
        $animalId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('vaccine_name', 'Vaccine')->required('date_administered', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalVaccination::create($animalId, [
            'vaccine_name' => $this->input('vaccine_name'),
            'date_administered' => $this->input('date_administered'),
            'next_due_date' => $this->input('next_due_date'),
            'administered_by' => $this->input('administered_by'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'animal_vaccinations', (string) $recordId, null, AnimalVaccination::find($recordId));

        $this->flash('success', 'Vaccination recorded.');
        $this->redirect("/livestock/{$animalId}");
    }

    // --- Feeding ---

    public function addFeeding(array $params): void
    {
        $animalId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('feed_type', 'Feed type')->required('feeding_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalFeeding::create($animalId, [
            'feed_type' => $this->input('feed_type'),
            'quantity' => $this->input('quantity', ''),
            'unit' => $this->input('unit'),
            'feeding_date' => $this->input('feeding_date'),
            'cost' => $this->input('cost', ''),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'animal_feedings', (string) $recordId, null, AnimalFeeding::find($recordId));

        $this->flash('success', 'Feeding recorded.');
        $this->redirect("/livestock/{$animalId}");
    }

    // --- Weight ---

    public function addWeight(array $params): void
    {
        $animalId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('weight_kg', 'Weight')->numeric('weight_kg', 'Weight')->required('recorded_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalWeight::create($animalId, [
            'weight_kg' => $this->input('weight_kg'),
            'recorded_date' => $this->input('recorded_date'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'animal_weights', (string) $recordId, null, AnimalWeight::find($recordId));

        $this->flash('success', 'Weight recorded.');
        $this->redirect("/livestock/{$animalId}");
    }

    // --- Treatment ---

    public function addTreatment(array $params): void
    {
        $animalId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('condition_name', 'Condition')->required('treatment', 'Treatment')->required('treatment_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalTreatment::create($animalId, [
            'condition_name' => $this->input('condition_name'),
            'treatment' => $this->input('treatment'),
            'treatment_date' => $this->input('treatment_date'),
            'administered_by' => $this->input('administered_by'),
            'cost' => $this->input('cost', ''),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'animal_treatments', (string) $recordId, null, AnimalTreatment::find($recordId));

        $this->flash('success', 'Treatment recorded.');
        $this->redirect("/livestock/{$animalId}");
    }

    // --- Breeding ---

    public function addBreeding(array $params): void
    {
        $animalId = (int) $params['id'];
        $validator = (new Validator($_POST))->required('breeding_date', 'Breeding date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalBreeding::create($animalId, [
            'mate_description' => $this->input('mate_description'),
            'breeding_date' => $this->input('breeding_date'),
            'expected_due_date' => $this->input('expected_due_date'),
            'outcome' => $this->input('outcome', 'pending'),
            'offspring_count' => $this->input('offspring_count', ''),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'animal_breeding', (string) $recordId, null, AnimalBreeding::find($recordId));

        $this->flash('success', 'Breeding record added.');
        $this->redirect("/livestock/{$animalId}");
    }

    // --- Production ---

    public function addProduction(array $params): void
    {
        $animalId = (int) $params['id'];
        $validator = (new Validator($_POST))
            ->required('production_type', 'Production type')
            ->required('quantity', 'Quantity')->numeric('quantity', 'Quantity')
            ->required('production_date', 'Date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalProduction::create($animalId, [
            'production_type' => $this->input('production_type'),
            'quantity' => $this->input('quantity'),
            'unit' => $this->input('unit'),
            'production_date' => $this->input('production_date'),
            'notes' => $this->input('notes'),
        ]);
        AuditLogger::log('create', 'animal_production', (string) $recordId, null, AnimalProduction::find($recordId));

        $this->flash('success', 'Production recorded.');
        $this->redirect("/livestock/{$animalId}");
    }

    // --- Mortality (terminal) ---

    public function recordMortality(array $params): void
    {
        $animalId = (int) $params['id'];
        $animal = Animal::find($animalId);
        if (!$animal || $animal['status'] !== 'active') {
            $this->flash('danger', 'Only an active animal can have a mortality record.');
            $this->redirect("/livestock/{$animalId}");
        }

        $validator = (new Validator($_POST))->required('death_date', 'Date of death');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalMortality::create($animalId, [
            'death_date' => $this->input('death_date'),
            'cause' => $this->input('cause'),
            'notes' => $this->input('notes'),
        ]);
        Animal::setStatus($animalId, 'deceased');

        AuditLogger::log('record_mortality', 'animal_mortality', (string) $recordId, null, ['animal_id' => $animalId]);

        $this->flash('success', 'Mortality recorded. This animal is now marked deceased.');
        $this->redirect("/livestock/{$animalId}");
    }

    // --- Sale (terminal) ---

    public function recordSale(array $params): void
    {
        $animalId = (int) $params['id'];
        $animal = Animal::find($animalId);
        if (!$animal || $animal['status'] !== 'active') {
            $this->flash('danger', 'Only an active animal can be recorded as sold.');
            $this->redirect("/livestock/{$animalId}");
        }

        $validator = (new Validator($_POST))
            ->required('buyer_name', 'Buyer')
            ->required('sale_price', 'Sale price')->numeric('sale_price', 'Sale price')
            ->required('sale_date', 'Sale date');
        if ($validator->fails()) {
            $this->flash('danger', $validator->firstError());
            $this->redirect("/livestock/{$animalId}");
        }

        $recordId = AnimalSale::create($animalId, [
            'buyer_name' => $this->input('buyer_name'),
            'sale_price' => $this->input('sale_price'),
            'sale_date' => $this->input('sale_date'),
            'notes' => $this->input('notes'),
        ]);
        Animal::setStatus($animalId, 'sold');

        AuditLogger::log('record_sale', 'animal_sales', (string) $recordId, null, ['animal_id' => $animalId]);

        $this->flash('success', 'Sale recorded. This animal is now marked sold.');
        $this->redirect("/livestock/{$animalId}");
    }
}
