<?php 
// app/Models/ExamRegistration.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ExamRegistration extends Model {
  protected $fillable = [
    'exam_id','student_id','faculty_id','semester','batch','exam_roll_no','token_no','amount'
  ];
  public function exam(){ return $this->belongsTo(Exam::class); }
  public function student(){ return $this->belongsTo(Student::class); }
  public function faculty(){ return $this->belongsTo(Faculty::class); }
  public function subjects(){ return $this->hasMany(ExamRegistrationSubject::class); }
}
