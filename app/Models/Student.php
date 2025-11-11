<?php
// app/Models/Student.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Student extends Model {
  protected $fillable = ['name','symbol_no','faculty_id','semester'];
  public function faculty(){ return $this->belongsTo(Faculty::class); }
}