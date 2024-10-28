<?php

namespace App\Http\Controllers;
use App\Models\User;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getPublicKey($id)
    {
        $user = User::findOrFail($id);
        return response($user->public_key, 200)
               ->header('Content-Type', 'text/plain');
    }
}
