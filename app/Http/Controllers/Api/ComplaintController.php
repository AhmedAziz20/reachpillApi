<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Complaint;

class ComplaintController extends Controller
{
    public function addComplaint(Request $request){
        $validatedComplaint = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        $complaint = auth()->user()->complaint()->create($validatedComplaint);

        return response()->json($complaint,201);
    }

    public function getAllComplaints(){
        $complaints = Complaint::with('user')->paginate(20);

        return response()->json($complaints,200);
    }


}
