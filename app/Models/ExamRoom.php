<?php
// app/Models/ExamRoom.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class ExamRoom extends Model {
  protected $fillable = ['exam_id','room_id','rows_col1','rows_col2','rows_col3','observers_required'];
}
function room() {
    return $this->belongsTo(Room::class, 'room_id');
}   
