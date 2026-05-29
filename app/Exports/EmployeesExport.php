<?php

namespace App\Exports;

use App\Models\Employee;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class EmployeesExport implements FromCollection, WithHeadings, WithMapping
{
    /**
     * Return all employees for export.
     */
    public function collection()
    {
        return Employee::latest()->get();
    }

    /**
     * Set column headings.
     */
    public function headings(): array
    {
        return [
            'Employee ID',
            'Name',
            'Email',
            'Mobile',
            'Department',
            'Designation',
            'Joining Date',
            'Status',
        ];
    }

    /**
     * Map each row of employee data.
     */
    public function map($employee): array
    {
        return [
            $employee->employee_id,
            $employee->name,
            $employee->email,
            $employee->mobile,
            $employee->department,
            $employee->designation,
            $employee->joining_date,
            $employee->status,
        ];
    }
}
