<?php
namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
abstract class Controller
{
    protected function isUserLoggedIn(): bool
    {
        return Auth::check();
    }
    
    protected function userData(): User
    {
        return Auth::user();
    }
}
