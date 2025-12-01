<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RequirementController extends Controller {
  public function cancellation(Request $request) {
    return view('cancellation');
  }
  public function transfer(Request $request) {
    return view('transfer');
  }
}
