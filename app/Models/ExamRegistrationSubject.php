<?php 
// app/Models/ExamRegistrationSubject.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ExamRegistrationSubject extends Model {
  protected $fillable = [
    'exam_registration_id','faculty_id','faculty_semester_subject_id',
    'subject_code','th_taking','p_taking'
  ];
  public function registration(){ return $this->belongsTo(ExamRegistration::class,'exam_registration_id'); }
  public function faculty(){ return $this->belongsTo(Faculty::class); }
  public function fss(){ return $this->belongsTo(FacultySemesterSubject::class,'faculty_semester_subject_id'); }
}
