<?php

namespace App\Http\Controllers\Admin;

use App\Exports\FeeStructureExport;
use App\Http\Controllers\Controller;
use App\Imports\FeeStructureImport;
use App\Models\FeeStructure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class FeeStructureController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $feeStructures = FeeStructure::when($search, function ($query, $search) {
            return $query->where('program_type', 'like', "%{$search}%")
                        ->orWhere('program_name', 'like', "%{$search}%");
        })->paginate(10);
    
        return view('admin.fee_structures.index', compact('feeStructures'));
    }
    public function create()
    {
        return view('admin.fee_structures.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'program_type' => 'required|string',
            'program_name' => 'required|string',
            'first_year' => 'required|numeric',
            'continuing_year' => 'required|numeric',
            'final_year' => 'required|numeric',
        ]);

        FeeStructure::create($request->all());

        return redirect()->route('fee_structures.index');
    }

    public function edit(FeeStructure $feeStructure)
    {
        return view('admin.fee_structures.edit', compact('feeStructure'));
    }

    public function update(Request $request, FeeStructure $feeStructure)
    {
        $request->validate([
            'program_type' => 'required|string',
            'program_name' => 'required|string',
            'first_year' => 'required|numeric',
            'continuing_year' => 'required|numeric',
            'final_year' => 'required|numeric',
        ]);

        $feeStructure->update($request->all());

        return redirect()->route('fee_structures.index');
    }

    public function destroy(FeeStructure $feeStructure)
    {
        $feeStructure->delete();
        return redirect()->route('fee_structures.index');
    }

    public function downloadTemplate()
    {
        // Generate Excel template with sample data based on PDF structure
        $data = $this->getFeeStructureData();
        return Excel::download(new FeeStructureExport($data), 'fee_structure_template.xlsx');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            Excel::import(new FeeStructureImport, $request->file('file'));
            return redirect()->route('fee_structures.index')->with('success', 'Fee structures imported successfully.');
        } catch (\Exception $e) {
            Log::error('Fee structure import error: ' . $e->getMessage());
            return redirect()->route('fee_structures.index')->with('error', 'Failed to import fee structures: ' . $e->getMessage());
        }
    }

    private function getFeeStructureData()
    {
        // Extracted from Fee_Structure2024-2025.pdf (simplified for template)
        return [
            // Undergraduate Tuition Fees (A)
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Arts with Education (BA Ed)',
                'first_year' => 1200000,
                'continuing_year' => 1200000,
                'final_year' => 1200000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Science with Education (BSc Ed)',
                'first_year' => 1700000,
                'continuing_year' => 1700000,
                'final_year' => 1700000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Science in Nursing (BSc Nursing)',
                'first_year' => 3000000,
                'continuing_year' => 3000000,
                'final_year' => 2500000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Theology (BATH)',
                'first_year' => 1000000,
                'continuing_year' => 1000000,
                'final_year' => 1000000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Pharmacy (BPharm)',
                'first_year' => 3500000,
                'continuing_year' => 3500000,
                'final_year' => 3000000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Science in Information Technology (BSc IT)',
                'first_year' => 1700000,
                'continuing_year' => 1700000,
                'final_year' => 1700000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Accounting and Finance (BAF)',
                'first_year' => 1300000,
                'continuing_year' => 1300000,
                'final_year' => 1300000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'BBA (Human Resources Management)',
                'first_year' => 1300000,
                'continuing_year' => 1300000,
                'final_year' => 1300000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'BBA (Marketing Management)',
                'first_year' => 1300000,
                'continuing_year' => 1300000,
                'final_year' => 1300000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'BBA (Health Services Management)',
                'first_year' => 0,
                'continuing_year' => 0,
                'final_year' => 1600000,
            ],
            [
                'program_type' => 'TUITION_FEE_UNDERGRADUATE',
                'program_name' => 'Bachelor of Commerce with Education (BCom Ed)',
                'first_year' => 1600000,
                'continuing_year' => 1600000,
                'final_year' => 1200000,
            ],
            // Non-Degree Tuition Fees (B)
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Diploma in Business Administration (Accounting)',
                'first_year' => 1000000,
                'continuing_year' => 0,
                'final_year' => 1000000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Diploma in Business Administration (Procurement)',
                'first_year' => 1000000,
                'continuing_year' => 0,
                'final_year' => 1000000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Diploma in Business Administration (Human Resources Management)',
                'first_year' => 1000000,
                'continuing_year' => 0,
                'final_year' => 1000000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Diploma in Business Administration (Marketing Management)',
                'first_year' => 1000000,
                'continuing_year' => 0,
                'final_year' => 1000000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Ordinary Diploma in Community Development',
                'first_year' => 1000000,
                'continuing_year' => 0,
                'final_year' => 1000000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Ordinary Diploma in Medical Laboratory Science',
                'first_year' => 1800000,
                'continuing_year' => 1800000,
                'final_year' => 1800000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Ordinary Diploma in Nursing and Midwifery',
                'first_year' => 1800000,
                'continuing_year' => 1800000,
                'final_year' => 1800000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Ordinary Diploma in Pharmaceutical Science',
                'first_year' => 1800000,
                'continuing_year' => 1800000,
                'final_year' => 1800000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Basic Technician Certificate in Pharmaceutical Science',
                'first_year' => 1800000,
                'continuing_year' => 0,
                'final_year' => 1800000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Technician Certificate in Pharmaceutical Science',
                'first_year' => 1800000,
                'continuing_year' => 0,
                'final_year' => 1800000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Basic Technician Certificate in Community Development',
                'first_year' => 1000000,
                'continuing_year' => 0,
                'final_year' => 1000000,
            ],
            [
                'program_type' => 'TUITION_FEE_NON_DEGREE',
                'program_name' => 'Technician Certificate in Community Development',
                'first_year' => 1000000,
                'continuing_year' => 0,
                'final_year' => 1000000,
            ],
            // Postgraduate Tuition Fees (C)
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Arts in Community Development',
                'first_year' => 3190000, // 1st + 2nd semester
                'continuing_year' => 0,
                'final_year' => 1595000, // 3rd semester
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Arts in Applied Linguistics',
                'first_year' => 3190000,
                'continuing_year' => 0,
                'final_year' => 1595000,
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Arts in Education',
                'first_year' => 3190000,
                'continuing_year' => 0,
                'final_year' => 1595000,
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Business Administration (Corporate Management)',
                'first_year' => 3190000,
                'continuing_year' => 0,
                'final_year' => 1595000,
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Business Administration (Accounting & Finance)',
                'first_year' => 3190000,
                'continuing_year' => 0,
                'final_year' => 1635000,
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Business Administration (Human Resource Management)',
                'first_year' => 3190000,
                'continuing_year' => 0,
                'final_year' => 1595000,
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Business Administration (Marketing)',
                'first_year' => 3190000,
                'continuing_year' => 0,
                'final_year' => 1635000,
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Business Administration (Procurement)',
                'first_year' => 3190000,
                'continuing_year' => 0,
                'final_year' => 1595000,
            ],
            [
                'program_type' => 'TUITION_FEE_POSTGRADUATE',
                'program_name' => 'Master of Pharmacy in Pharmaceutical Public Health Management',
                'first_year' => 3900000,
                'continuing_year' => 0,
                'final_year' => 1500000,
            ],
            // Compulsory Charges (D, E, F, G)
            [
                'program_type' => 'COMPULSORY_CHARGE_DIPLOMA',
                'program_name' => 'Compulsory Charges for Diploma Programmes',
                'first_year' => 420400,
                'continuing_year' => 250400,
                'final_year' => 350400,
            ],
            [
                'program_type' => 'COMPULSORY_CHARGE_CERTIFICATE',
                'program_name' => 'Compulsory Charges for Certificate Programmes (One Year)',
                'first_year' => 530400, // Semester I + II
                'continuing_year' => 0,
                'final_year' => 0,
            ],
            [
                'program_type' => 'COMPULSORY_CHARGE_CERTIFICATE',
                'program_name' => 'Compulsory Charges for Certificate Programmes (Two Years)',
                'first_year' => 420400,
                'continuing_year' => 0,
                'final_year' => 350400,
            ],
            [
                'program_type' => 'COMPULSORY_CHARGE_BACHELOR',
                'program_name' => 'Compulsory Charges for Bachelor Degree',
                'first_year' => 420400,
                'continuing_year' => 250400,
                'final_year' => 350400,
            ],
            [
                'program_type' => 'COMPULSORY_CHARGE_POSTGRADUATE',
                'program_name' => 'Compulsory Charges for Postgraduate Students',
                'first_year' => 490400,
                'continuing_year' => 0,
                'final_year' => 310400,
            ],
        ];
    }
}
