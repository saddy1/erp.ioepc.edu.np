<?php
// app/Models/Invigilator.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Invigilator extends Model {
  protected $fillable = ['name','type','faculty_id'];
}
