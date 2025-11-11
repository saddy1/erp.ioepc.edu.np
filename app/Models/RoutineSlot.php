<?php
// app/Models/RoutineSlot.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class RoutineSlot extends Model {
  protected $fillable = ['exam_date','start_time','end_time','semester','exam_title','batch'];

  public function subjects(){ return $this->hasMany(RoutineSubject::class); }
}