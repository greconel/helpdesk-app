<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index()
    {
        $customers = Customer::withCount('tickets')->orderBy('name')->get();
        return view('customers.index', compact('customers'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'motion_project_id' => 'nullable|string|max:255',
        ]);

        $customer->update($validated);

        return back()->with('success', 'Klant bijgewerkt.');
    }
}