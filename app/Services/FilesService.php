<?php

namespace App\Services;

use App\Models\FilesModel;

class FilesService
{
    private $filesModel;

    public function __construct()
    {
        $this->filesModel = new FilesModel();
    }

    public function loadFiles()
    {
        return $this->filesModel::all()->sortByDesc('created_at');
    }

    public function loadPaginate()
    {
        return $this->filesModel::paginate(Config::get('crm_settings.pagination_size'));
    }

    public function loadRules()
    {
        return $this->filesModel->getRules('STORE');
    }

    public function execute($allInputs)
    {
        return $this->filesModel->insertRow($allInputs);
    }

    public function getFile(int $id)
    {
        return $this->filesModel::find($id);
    }

    public function update($id, $allInputs)
    {
        return $this->filesModel->updateRow($id, $allInputs);
    }

    public function loadIsActive($id, $value)
    {
        return $this->filesModel->setActive($id, $value);
    }

    public function loadSearch($getValueInput)
    {
        return count($this->filesModel->trySearchFilesByValue('name', $getValueInput, 10));
    }
}