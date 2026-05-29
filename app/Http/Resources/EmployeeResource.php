<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'joining_date' => $this->joining_date,
            'department' => $this->department,
            'designation' => $this->designation,
            'status' => $this->status,
            'address_line' => $this->address_line,
            'pincode' => $this->pincode,
            'country_id' => $this->country_id,
            'state_id' => $this->state_id,
            'city_id' => $this->city_id,
            'country' => $this->country ? $this->country->name : null,
            'state' => $this->state ? $this->state->name : null,
            'city' => $this->city ? $this->city->name : null,
            'documents' => EmployeeDocumentResource::collection($this->whenLoaded('documents')),
            'documents_count' => $this->documents_count ?? ($this->relationLoaded('documents') ? $this->documents->count() : $this->documents()->count()),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->toISOString() : null,
            'created_at' => $this->created_at ? $this->created_at->toISOString() : null,
            'updated_at' => $this->updated_at ? $this->updated_at->toISOString() : null,
        ];
    }
}
