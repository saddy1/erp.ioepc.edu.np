<?php

// app/Models/InvigilatorAssignment.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class InvigilatorAssignment extends Model {
  protected $fillable = ['exam_id','room_id','invigilator_id'];
}
