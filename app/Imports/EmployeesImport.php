<?php

namespace App\Imports;

use App\Models\Employee;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class EmployeesImport implements ToCollection
{
    protected $errors = [];
    protected $importedCount = 0;

    /**
     * Import rows from collection.
     */
    public function collection(Collection $rows)
    {
        $rowNum = 1;
        
        // Detect and skip header row
        $hasHeader = false;
        if ($rows->isNotEmpty()) {
            $firstRow = $rows->first()->toArray();
            $firstCell = strtolower(trim($firstRow[0] ?? ''));
            if (str_contains($firstCell, 'id') || str_contains($firstCell, 'employee')) {
                $hasHeader = true;
            }
        }

        foreach ($rows as $index => $row) {
            $rowNum = $index + 1;
            if ($hasHeader && $index === 0) {
                continue;
            }

            $rowArray = $row->toArray();
            
            // Skip empty rows
            if (empty(array_filter($rowArray))) {
                continue;
            }

            $employeeId  = trim($rowArray[0] ?? '');
            $name         = trim($rowArray[1] ?? '');
            $email        = trim($rowArray[2] ?? '');
            $mobile       = trim($rowArray[3] ?? '');
            $department   = trim($rowArray[4] ?? '');
            $designation  = trim($rowArray[5] ?? '');
            $joiningDate  = trim($rowArray[6] ?? '');
            $status       = trim($rowArray[7] ?? 'Active');

            // Skip if completely empty row
            if (empty($employeeId) && empty($name) && empty($email)) {
                continue;
            }

            // Basic validation
            if (empty($employeeId)) {
                $this->errors[] = "Row {$rowNum}: Employee ID is required.";
                continue;
            }
            if (empty($name)) {
                $this->errors[] = "Row {$rowNum}: Name is required.";
                continue;
            }
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->errors[] = "Row {$rowNum}: A valid email address is required.";
                continue;
            }
            if (empty($mobile)) {
                $this->errors[] = "Row {$rowNum}: Mobile number is required.";
                continue;
            }

            // Normalize status
            $status = in_array(strtolower($status), ['active', 'inactive']) ? ucfirst(strtolower($status)) : 'Active';

            // Normalize joining date
            $formattedDate = null;
            if (!empty($joiningDate)) {
                // Maatwebsite/Excel sometimes reads dates as serial numbers (Excel float format)
                if (is_numeric($joiningDate)) {
                    // Convert Excel serial number to date
                    $formattedDate = date('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToTimestamp($joiningDate));
                } else {
                    $timestamp = strtotime($joiningDate);
                    if ($timestamp) {
                        $formattedDate = date('Y-m-d', $timestamp);
                    }
                }
            }

            // check unique constraints
            $existingById = Employee::withTrashed()->where('employee_id', $employeeId)->first();
            $existingByEmail = Employee::withTrashed()
                ->where('email', $email)
                ->where('employee_id', '!=', $employeeId)
                ->first();

            if ($existingByEmail) {
                $this->errors[] = "Row {$rowNum}: The email '{$email}' is already used by another employee.";
                continue;
            }

            $employeeData = [
                'employee_id'  => $employeeId,
                'name'         => $name,
                'email'        => $email,
                'mobile'       => $mobile,
                'department'   => $department ?: null,
                'designation'  => $designation ?: null,
                'joining_date' => $formattedDate,
                'status'       => $status,
            ];

            if ($existingById) {
                // Update existing record and restore if soft-deleted
                $existingById->update($employeeData);
                if ($existingById->trashed()) {
                    $existingById->restore();
                }
            } else {
                // Create new record
                Employee::create($employeeData);
            }

            $this->importedCount++;
        }
    }

    /**
     * Get list of errors.
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get count of imported records.
     */
    public function getImportedCount(): int
    {
        return $this->importedCount;
    }
}
