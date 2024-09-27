<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Employee;


class EmployeeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        // Get filter criteria from request
        $nameFilter = $request->input('name', '');

        // Query to filter employees with non-empty names
        $employeesQuery = Employee::query();

        // Filter by non-empty names
        if ($nameFilter) {
            $employeesQuery->where('name', 'like',  $nameFilter . '%');
        }

        // Ensure names are not empty
        // $employeesQuery->whereNotNull('name')->where('name', '!=', '');

        // Paginate results
        $employees = $employeesQuery->paginate(10); // 10 items per page

        return view('devices.map_id', compact('employees'));
    }


    /**
     * Store or update an employee.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|integer',
            'employee_name' => 'required|string|max:255',
        ]);

        $employee = Employee::updateOrCreate(
            ['employee_id' => $request->employee_id],
            ['name' => $request->employee_name]
        );

        return redirect()->route('employee.MapId');
    }

}
