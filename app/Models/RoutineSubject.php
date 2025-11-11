<?php

// app/Models/RoutineSubject.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RoutineSubject extends Model {
  protected $fillable = ['routine_slot_id','faculty_id','subject_code'];

  public function slot(){ return $this->belongsTo(RoutineSlot::class,'routine_slot_id'); }
  public function faculty(){ return $this->belongsTo(Faculty::class); }

  public function subject(){ return $this->belongsTo(FacultySemesterSubject::class,'subject_code','subject_code'); }

    public function subjectName()
    {
        return $this->hasOne(FacultySemesterSubject::class, 'subject_code', 'subject_code')
            ->where('faculty_id', $this->faculty_id);
    }
}
