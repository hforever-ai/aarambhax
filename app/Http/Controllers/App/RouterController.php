<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\Routing\DraftRouter;
use Illuminate\Http\Request;

class RouterController extends Controller
{
    /**
     * Canonical document types the router recognises. Mirrors the catalogue
     * extractor's vocabulary. Surfaced as checkboxes in the UI.
     */
    public const DOC_TYPES = [
        'Identity documents' => [
            'aadhaar' => 'Aadhaar card',
            'pan_card' => 'PAN card',
        ],
        'Criminal-side' => [
            'FIR' => 'FIR',
            'charge_sheet' => 'Charge-sheet',
            'medical_report' => 'Medical / injury report',
            'fsl_report' => 'FSL / forensic report',
            'section_164_statement' => '§164 statement',
            'remand_papers' => 'Remand papers',
            'warrant' => 'Warrant',
            'summons' => 'Summons',
            'school_record' => 'School / DOB record',
        ],
        'Civil-side' => [
            'lower_court_order' => 'Lower court order',
            'lower_court_judgment' => 'Lower court judgment',
            'legal_notice' => 'Legal notice',
            'cheque' => 'Cheque',
            'bank_statement' => 'Bank statement',
            'loan_document' => 'Loan document',
            'employment_letter' => 'Employment / appointment letter',
            'salary_slip' => 'Salary slip',
        ],
        'Family / Matrimonial' => [
            'marriage_certificate' => 'Marriage certificate',
            'death_certificate' => 'Death certificate',
            'prior_complaint' => 'Prior complaint (police / mahila)',
            'photographs' => 'Photographs',
            'witness_statement' => 'Witness statement',
        ],
        'Property / Revenue' => [
            'sale_deed' => 'Registered sale deed',
            'gift_deed' => 'Gift deed',
            'partition_deed' => 'Partition deed',
            'will' => 'Will',
            'lease_deed' => 'Lease deed',
            'rent_agreement' => 'Rent agreement',
            'khasra' => 'Khasra extract',
            'khatauni' => 'Khatauni / B-1 / P-II',
            'mutation_entry' => 'Mutation entry',
            'power_of_attorney' => 'Power of Attorney',
        ],
        'Specialty' => [
            'rera_registration' => 'RERA registration',
            'builder_agreement' => 'Builder-buyer agreement',
            'invoice_receipt' => 'Invoice / receipt',
        ],
    ];

    public function show()
    {
        return view('app.router.show', [
            'docTypes' => self::DOC_TYPES,
        ]);
    }

    public function recommend(Request $request)
    {
        $data = $request->validate([
            'documents'  => ['required', 'array', 'min:1'],
            'documents.*'=> ['string', 'max:80'],
            'forum'      => ['nullable', 'string', 'max:50'],
            'language'   => ['nullable', 'string', 'max:10'],
        ]);

        $filters = array_filter([
            'forum'    => $data['forum'] ?? null,
            'language' => $data['language'] ?? null,
        ]);

        $router = DraftRouter::make();
        $result = $router->recommend($data['documents'], $filters);

        return view('app.router.show', [
            'docTypes' => self::DOC_TYPES,
            'result'   => $result,
            'submitted'=> [
                'documents' => $data['documents'],
                'forum'     => $data['forum'] ?? '',
                'language'  => $data['language'] ?? '',
            ],
        ]);
    }
}
