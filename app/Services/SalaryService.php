<?php
namespace App\Services;
use App\Models\Position;
use App\Repositories\SalaryRepository;
use Illuminate\Validation\ValidationException;
class SalaryService {
    private SalaryRepository $salary_repository;
    public function __construct(SalaryRepository $salary_repository) {
        $this->salary_repository = $salary_repository;
    }
    public function getAll(array $data = ['*']) {
        return $this->salary_repository->getAll($data);
    }
    public function getById(int $id, array $data = ['*']){
        return $this->salary_repository->getById($id, $data);
    }
    public function create(array $data)
    {
        $this->validateSalary($data);
        return $this->salary_repository->create($data);
    }
    public function update(int $id, array $data)
    {
        $this->validateSalary($data);
        return $this->salary_repository->update($id, $data);
    }
    public function delete(int $id) {
        return $this->salary_repository->delete($id);
    }
    private function validateSalary(array $data): void
    {
        if ($data['gaji_pokok'] < 0) {
            throw ValidationException::withMessages([
                'gaji_pokok' => 'Gaji pokok tidak boleh kurang dari 0.',
            ]);
        }

        if ($data['tahun_masa_kerja'] < 0) {
            throw ValidationException::withMessages([
                'tahun_masa_kerja' => 'Masa kerja tidak boleh kurang dari 0.',
            ]);
        }
    }

}
