<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use App\Exports\EmployeesExport;
use App\Imports\EmployeesImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\EmployeeDocument;
use Illuminate\Support\Facades\Storage;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeDocumentResource;

class EmployeeController extends Controller
{
    // Get All Employees
    public function index()
    {
        $employees = Employee::with(['country', 'state', 'city'])->withCount('documents')->latest()->get();
        return EmployeeResource::collection($employees);
    }

    // Add Employee
    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_id' => 'required|unique:employees',
            'name' => 'required',
            'email' => 'required|email|unique:employees',
            'mobile' => 'required',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'nullable|date',
            'status' => 'required',
            'department' => 'nullable',
            'designation' => 'nullable',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'address_line' => 'nullable|string',
            'pincode' => 'nullable|string',
            'aadhaar_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'resume' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'password' => 'nullable|string|min:6'
        ]);

        $employee = Employee::create([
            'employee_id' => $validated['employee_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'],
            'gender' => $validated['gender'] ?? 'Male',
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'status' => $validated['status'],
            'department' => $validated['department'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'country_id' => $validated['country_id'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'address_line' => $validated['address_line'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'password' => !empty($validated['password']) ? bcrypt($validated['password']) : null,
        ]);

        $fileFields = [
            'aadhaar_card' => 'Aadhaar Card',
            'pan_card' => 'PAN Card',
            'resume' => 'Resume',
            'profile_image' => 'Profile Image',
        ];

        foreach ($fileFields as $key => $type) {
            if ($request->hasFile($key)) {
                $this->storeFile($request->file($key), $employee->id, $type);
            }
        }

        if ($request->hasFile('other_documents')) {
            $otherFiles = $request->file('other_documents');
            if (is_array($otherFiles)) {
                foreach ($otherFiles as $file) {
                    $this->storeFile($file, $employee->id, 'Other Documents');
                }
            } else {
                $this->storeFile($otherFiles, $employee->id, 'Other Documents');
            }
        }

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $this->storeFile($file, $employee->id, 'Other Documents');
            }
        }

        $employee = Employee::with(['country', 'state', 'city', 'documents'])->withCount('documents')->findOrFail($employee->id);

        return response()->json([
            'message' => 'Employee Created Successfully',
            'employee' => new EmployeeResource($employee)
        ], 201);
    }

    // Single Employee
    public function show($id)
    {
        $user = auth()->user();
        if ($user instanceof \App\Models\Employee && $user->id != $id) {
            return response()->json(['message' => 'Forbidden. You cannot access this employee profile.'], 403);
        }
        $employee = Employee::with(['country', 'state', 'city', 'documents'])->withCount('documents')->findOrFail($id);
        return new EmployeeResource($employee);
    }

    // Update Employee
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        if ($user instanceof \App\Models\Employee && $user->id != $id) {
            return response()->json(['message' => 'Forbidden. You cannot update this employee profile.'], 403);
        }
        $employee = Employee::findOrFail($id);

        $validated = $request->validate([
            'employee_id' => 'required|unique:employees,employee_id,' . $id,
            'name' => 'required',
            'email' => 'required|email|unique:employees,email,' . $id,
            'mobile' => 'required',
            'gender' => 'nullable|in:Male,Female,Other',
            'date_of_birth' => 'nullable|date',
            'joining_date' => 'nullable|date',
            'status' => 'required',
            'department' => 'nullable',
            'designation' => 'nullable',
            'country_id' => 'nullable|exists:countries,id',
            'state_id' => 'nullable|exists:states,id',
            'city_id' => 'nullable|exists:cities,id',
            'address_line' => 'nullable|string',
            'pincode' => 'nullable|string',
            'aadhaar_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'pan_card' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'resume' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'profile_image' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
            'other_documents' => 'nullable|array',
            'other_documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'documents' => 'nullable|array',
            'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',
            'password' => 'nullable|string|min:6'
        ]);

        $employeeData = [
            'employee_id' => $validated['employee_id'],
            'name' => $validated['name'],
            'email' => $validated['email'],
            'mobile' => $validated['mobile'],
            'gender' => $validated['gender'] ?? 'Male',
            'date_of_birth' => $validated['date_of_birth'] ?? null,
            'joining_date' => $validated['joining_date'] ?? null,
            'status' => $validated['status'],
            'department' => $validated['department'] ?? null,
            'designation' => $validated['designation'] ?? null,
            'country_id' => $validated['country_id'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'address_line' => $validated['address_line'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
        ];

        if (!empty($validated['password'])) {
            $employeeData['password'] = bcrypt($validated['password']);
        }

        $employee->update($employeeData);

        $fileFields = [
            'aadhaar_card' => 'Aadhaar Card',
            'pan_card' => 'PAN Card',
            'resume' => 'Resume',
            'profile_image' => 'Profile Image',
        ];

        foreach ($fileFields as $key => $type) {
            if ($request->hasFile($key)) {
                // Delete old document of this type
                $oldDoc = EmployeeDocument::where('employee_id', $employee->id)
                    ->where('document_type', $type)
                    ->first();
                if ($oldDoc) {
                    if (Storage::disk('public')->exists($oldDoc->file_path)) {
                        Storage::disk('public')->delete($oldDoc->file_path);
                    }
                    $oldDoc->delete();
                }

                $this->storeFile($request->file($key), $employee->id, $type);
            }
        }

        if ($request->hasFile('other_documents')) {
            $otherFiles = $request->file('other_documents');
            if (is_array($otherFiles)) {
                foreach ($otherFiles as $file) {
                    $this->storeFile($file, $employee->id, 'Other Documents');
                }
            } else {
                $this->storeFile($otherFiles, $employee->id, 'Other Documents');
            }
        }

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $this->storeFile($file, $employee->id, 'Other Documents');
            }
        }

        $employee = Employee::with(['country', 'state', 'city', 'documents'])->withCount('documents')->findOrFail($employee->id);

        return response()->json([
            'message' => 'Employee Updated Successfully',
            'employee' => new EmployeeResource($employee)
        ]);
    }

    // Soft Delete Employee
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);

        $employee->delete();

        return response()->json([
            'message' => 'Employee Deleted Successfully'
        ]);
    }

    // Deleted Employees
    public function deletedEmployees()
    {
        $deleted = Employee::onlyTrashed()->with(['country', 'state', 'city'])->withCount('documents')->get();
        return EmployeeResource::collection($deleted);
    }

    // Restore Employee
    public function restore($id)
    {
        $employee = Employee::onlyTrashed()
            ->findOrFail($id);

        $employee->restore();

        return response()->json([
            'message' => 'Employee Restored Successfully'
        ]);
    }

    // Permanent Delete Employee
    public function forceDelete($id)
    {
        $employee = Employee::onlyTrashed()
            ->findOrFail($id);

        // Delete all attached documents from disk first
        foreach ($employee->documents as $doc) {
            if (Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }
        }

        $employee->forceDelete();

        return response()->json([
            'message' => 'Employee Permanently Deleted Successfully'
        ]);
    }

    // export employee 
    public function export()
    {
        return Excel::download(new EmployeesExport, 'employees_' . date('Ymd_His') . '.xlsx');
    }

    // import funtion
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt'
        ]);

        $import = new EmployeesImport;
        Excel::import($import, $request->file('file'));

        return response()->json([
            'message' => "Import completed.",
            'imported_count' => $import->getImportedCount(),
            'errors' => $import->getErrors()
        ]);
    }

    /**
     * Store employee document helper.
     */
    private function storeFile($file, $employeeId, $documentType = 'Other Documents', $documentNumber = null, $documentName = null, $remarks = null)
    {
        $originalName = $file->getClientOriginalName();
        $mimeType = $file->getClientMimeType();
        $fileSize = $file->getSize();

        // Secure file storage under documents/
        $filePath = $file->store('documents', 'public');

        return EmployeeDocument::create([
            'employee_id' => $employeeId,
            'document_type' => $documentType,
            'document_number' => $documentNumber,
            'document_name' => $documentName ?: $originalName,
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'mime_type' => $mimeType,
            'uploaded_by' => 'Admin',
            'remarks' => $remarks,
        ]);
    }

    /**
     * Format a document for API response.
     */
    private function formatDoc($doc)
    {
        return [
            'id' => $doc->id,
            'employee_id' => $doc->employee_id,
            'document_type' => $doc->document_type,
            'document_number' => $doc->document_number,
            'document_name' => $doc->document_name,
            'file_path' => $doc->file_path,
            'file_size' => $doc->file_size,
            'mime_type' => $doc->mime_type,
            'uploaded_by' => $doc->uploaded_by,
            'remarks' => $doc->remarks,
            'file_url' => asset('storage/' . $doc->file_path),
            'created_at' => $doc->created_at->toISOString(),
            'updated_at' => $doc->updated_at->toISOString(),
        ];
    }

    /**
     * Get documents of employee.
     */
    public function getDocuments($employeeId)
    {
        $user = auth()->user();
        if ($user instanceof \App\Models\Employee && $user->id != $employeeId) {
            return response()->json(['message' => 'Forbidden. You cannot access these documents.'], 403);
        }
        $employee = Employee::findOrFail($employeeId);
        
        $documents = $employee->documents->map(function ($doc) {
            return $this->formatDoc($doc);
        });

        return response()->json($documents);
    }

    /**
     * Upload dynamic documents to employee.
     */
    public function uploadDocuments(Request $request, $employeeId)
    {
        $user = auth()->user();
        if ($user instanceof \App\Models\Employee && $user->id != $employeeId) {
            return response()->json(['message' => 'Forbidden. You cannot upload documents for this employee.'], 403);
        }
        $employee = Employee::findOrFail($employeeId);

        if ($request->hasFile('file')) {
            $documentType = $request->input('document_type');
            $documentNumber = $request->input('document_number');

            $rules = [
                'document_type' => 'required|string|in:Aadhaar Card,PAN Card,Bank Passbook,Resume,Offer Letter,Experience Letter,Education Certificate,Passport,Driving License,Other Documents',
                'document_name' => 'required|string|max:255',
                'remarks' => 'nullable|string',
                'file' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:5120', // Max 5MB
            ];

            if (in_array($documentType, ['Aadhaar Card', 'PAN Card', 'Passport', 'Driving License', 'Bank Passbook'])) {
                $rules['document_number'] = 'required|string';
            } else {
                $rules['document_number'] = 'nullable|string';
            }

            $request->validate($rules);

            // Custom format validations for document_number
            if ($documentType === 'Aadhaar Card' && !preg_match('/^\d{12}$/', $documentNumber)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['document_number' => ['Aadhaar Card number must be exactly 12 digits.']]
                ], 422);
            }

            if ($documentType === 'PAN Card' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i', $documentNumber)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['document_number' => ['PAN Card number must be a valid 10-character alphanumeric code (e.g. ABCDE1234F).']]
                ], 422);
            }

            if ($documentType === 'Passport' && !preg_match('/^[A-Z0-9]{8,12}$/i', $documentNumber)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['document_number' => ['Passport number must be a valid alphanumeric code of 8 to 12 characters.']]
                ], 422);
            }

            if ($documentType === 'Driving License' && !preg_match('/^[A-Z0-9\- ]{8,25}$/i', $documentNumber)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['document_number' => ['Driving License number must be valid alphanumeric characters (8 to 25 characters).']]
                ], 422);
            }

            if ($documentType === 'Bank Passbook' && !preg_match('/^\d{9,18}$/', $documentNumber)) {
                return response()->json([
                    'message' => 'The given data was invalid.',
                    'errors' => ['document_number' => ['Bank Account Number must be a numeric value between 9 and 18 digits.']]
                ], 422);
            }

            $file = $request->file('file');
            $doc = $this->storeFile(
                $file,
                $employee->id,
                $documentType,
                $documentNumber,
                $request->input('document_name'),
                $request->input('remarks')
            );

            return response()->json([
                'message' => 'Document uploaded successfully.',
                'document' => $this->formatDoc($doc)
            ], 201);
        } else {
            // Fallback for multi-upload legacy or basic form submits
            $request->validate([
                'documents' => 'required|array',
                'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240', // Max 10MB legacy
            ]);

            $uploaded = [];
            foreach ($request->file('documents') as $file) {
                $doc = $this->storeFile($file, $employee->id);
                $uploaded[] = $this->formatDoc($doc);
            }

            return response()->json([
                'message' => 'Documents uploaded successfully.',
                'documents' => $uploaded
            ]);
        }
    }

    /**
     * Get a single document details.
     */
    public function getDocument($id)
    {
        $doc = EmployeeDocument::findOrFail($id);
        $user = auth()->user();
        if ($user instanceof \App\Models\Employee && $user->id != $doc->employee_id) {
            return response()->json(['message' => 'Forbidden. You cannot access this document.'], 403);
        }
        return response()->json($this->formatDoc($doc));
    }

    /**
     * Update a single document's metadata or file.
     */
    public function updateDocument(Request $request, $id)
    {
        $doc = EmployeeDocument::findOrFail($id);
        $user = auth()->user();
        if ($user instanceof \App\Models\Employee && $user->id != $doc->employee_id) {
            return response()->json(['message' => 'Forbidden. You cannot update this document.'], 403);
        }

        $documentType = $request->input('document_type');
        $documentNumber = $request->input('document_number');

        $rules = [
            'document_type' => 'required|string|in:Aadhaar Card,PAN Card,Bank Passbook,Resume,Offer Letter,Experience Letter,Education Certificate,Passport,Driving License,Other Documents',
            'document_name' => 'required|string|max:255',
            'remarks' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,jpg,jpeg,png,webp|max:5120', // Max 5MB
        ];

        if (in_array($documentType, ['Aadhaar Card', 'PAN Card', 'Passport', 'Driving License', 'Bank Passbook'])) {
            $rules['document_number'] = 'required|string';
        } else {
            $rules['document_number'] = 'nullable|string';
        }

        $request->validate($rules);

        // Custom format validations for document_number
        if ($documentType === 'Aadhaar Card' && !preg_match('/^\d{12}$/', $documentNumber)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['document_number' => ['Aadhaar Card number must be exactly 12 digits.']]
            ], 422);
        }

        if ($documentType === 'PAN Card' && !preg_match('/^[A-Z]{5}[0-9]{4}[A-Z]{1}$/i', $documentNumber)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['document_number' => ['PAN Card number must be a valid 10-character alphanumeric code (e.g. ABCDE1234F).']]
            ], 422);
        }

        if ($documentType === 'Passport' && !preg_match('/^[A-Z0-9]{8,12}$/i', $documentNumber)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['document_number' => ['Passport number must be a valid alphanumeric code of 8 to 12 characters.']]
            ], 422);
        }

        if ($documentType === 'Driving License' && !preg_match('/^[A-Z0-9\- ]{8,25}$/i', $documentNumber)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['document_number' => ['Driving License number must be valid alphanumeric characters (8 to 25 characters).']]
            ], 422);
        }

        if ($documentType === 'Bank Passbook' && !preg_match('/^\d{9,18}$/', $documentNumber)) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => ['document_number' => ['Bank Account Number must be a numeric value between 9 and 18 digits.']]
            ], 422);
        }

        $doc->document_type = $documentType;
        $doc->document_number = $documentNumber;
        $doc->document_name = $request->input('document_name');
        $doc->remarks = $request->input('remarks');

        if ($request->hasFile('file')) {
            // Delete old file
            if (Storage::disk('public')->exists($doc->file_path)) {
                Storage::disk('public')->delete($doc->file_path);
            }

            // Store new file
            $file = $request->file('file');
            $filePath = $file->store('documents', 'public');
            $doc->file_path = $filePath;
            $doc->file_size = $file->getSize();
            $doc->mime_type = $file->getClientMimeType();
        }

        $doc->save();

        return response()->json([
            'message' => 'Document updated successfully.',
            'document' => $this->formatDoc($doc)
        ]);
    }

    /**
     * Delete document.
     */
    public function deleteDocument($id)
    {
        $document = EmployeeDocument::findOrFail($id);
        $user = auth()->user();
        if ($user instanceof \App\Models\Employee && $user->id != $document->employee_id) {
            return response()->json(['message' => 'Forbidden. You cannot delete this document.'], 403);
        }

        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return response()->json([
            'message' => 'Document deleted successfully.'
        ]);
    }
}