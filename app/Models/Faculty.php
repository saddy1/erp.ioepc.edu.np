<?php
// app/Models/Faculty.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Faculty extends Model {
  protected $fillable = ['name','code'];

    public function scopeCodeOrder($query)
    {
        $order = ['BCE', 'BEL', 'BEI', 'BCT', 'BEX', 'BME', 'BAG', 'BAR'];

        $list = "'" . implode("','", $order) . "'";

        // If you ONLY have these codes, this is enough:
        return $query->orderByRaw("FIELD(code, $list)");
        
        // If you might have extra codes and want them AFTER these, use this instead:
        // return $query->orderByRaw("FIELD(code, $list) = 0, FIELD(code, $list)");
    }
  public function semesterSubjects()
{
    return $this->hasMany(FacultySemesterSubject::class); 
}}