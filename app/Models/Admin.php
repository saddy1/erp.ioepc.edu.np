<?php
namespace App\Models;


use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;


class Admin extends Model
{
use HasFactory;


protected $fillable = ['name','email','password','contact','is_super_admin'];


protected $hidden = ['password'];


public function setPasswordAttribute($value)
{
$this->attributes['password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
}
}