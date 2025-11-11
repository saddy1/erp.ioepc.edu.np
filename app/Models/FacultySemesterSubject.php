<?php
// app/Models/FacultySemesterSubject.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class FacultySemesterSubject extends Model {
  protected $fillable = ['faculty_id','semester','batch','subject_code','subject_name'];
  public function faculty(){ return $this->belongsTo(Faculty::class); }



    public function subjectName()
    {
        return $this->hasOne(FacultySemesterSubject::class, 'subject_code', 'subject_code')
            ->where('faculty_id', $this->faculty_id);
    }
}

