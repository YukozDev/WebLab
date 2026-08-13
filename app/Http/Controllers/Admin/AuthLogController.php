<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuthLog;
use Illuminate\Http\Request;

class AuthLogController extends Controller
{
    public function index()
    {
        $logs = AuthLog::with('user')->orderBy('created_at', 'desc')->paginate(100);
        
        return view('admin.logs.index', compact('logs'));
    }
}
