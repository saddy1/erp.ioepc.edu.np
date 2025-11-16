@extends('Backend.layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

<h1 class="text-2xl font-bold mb-4">Edit Employee</h1>

<form method="POST" action="{{ route('employees.update',$employee) }}" class="space-y-4">
  @csrf
  @method('PUT')

  <div>
    <label>Full Name *</label>
    <input type="text" name="full_name" class="w-full border rounded px-3 py-2"
           value="{{ $employee->full_name }}" required>
  </div>

  <div>
    <label>Contact</label>
    <input type="text" name="contact" class="w-full border rounded px-3 py-2"
           value="{{ $employee->contact }}">
  </div>

  

  <div>
    <label>Employee Type *</label>
    <select name="employee_type" class="w-full border rounded px-3 py-2">
      <option value="faculty" @selected($employee->employee_type=='faculty')>Faculty</option>
      <option value="staff" @selected($employee->employee_type=='staff')>Staff</option>
    </select>
  </div>

  

  <div class="flex items-center gap-2">
    <input type="checkbox" name="is_active" value="1"
           @checked($employee->is_active)>
    <label>Active User</label>
  </div>

  <button class="bg-gray-900 text-white px-4 py-2 rounded">Update Employee</button>

</form>

</div>
@endsection
