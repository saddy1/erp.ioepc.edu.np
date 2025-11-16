<?php

namespace App\Http\Controllers\Admin;
use App\Models\Employee;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $q = Employee::query();
        if ($s = trim($request->get('search',''))) {
            $q->where('full_name','like',"%{$s}%");
        }
        $employees = $q->paginate(15)->appends($request->only('search'));
        return view('Backend.admin.employees.index', compact('employees'));
    }

    public function create()
    {
        return view('Backend.admin.employees.create');
    }

    public function store(Request $request)
{
    $data = $request->validate([
        'full_name'      => ['required','string','max:150'],
        'contact'        => ['nullable','string','max:30'],
        'employee_type'  => ['required','in:faculty,staff'],   // NEW

        'password'       => ['nullable','string','min:6','max:64'],
    ]);

    $payload = $data;

    // checkboxes
    $payload['is_active'] = $request->has('is_active');
    
    Employee::create($payload);

    return redirect()->route('employees.index')->with('success','Employee created.');
}

    public function edit(Employee $employee)
    {
        return view('Backend.admin.employees.edit', [
            'employee'    => $employee,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $request->validate([
            'full_name'     => ['required','string','max:150'],
            'contact'       => ['nullable','string','max:30'],
            'is_active'     => ['nullable','boolean'],
            'employee_type'  => ['required','in:faculty,staff'],  // NEW

        ]);

        $payload = [
            'full_name'     => $data['full_name'],
            'contact'       => $data['contact'] ?? null,
            'employee_type'  => $data['employee_type'],
            'is_active'     => (bool)($data['is_active'] ?? $employee->is_active),
        ];

        
        $employee->update($payload);

        return redirect()->route('employees.index')->with('success','Employee updated.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return back()->with('success','Employee deleted.');
    }
}
