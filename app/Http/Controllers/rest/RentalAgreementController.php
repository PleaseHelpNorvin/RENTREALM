<?php

namespace App\Http\Controllers\rest;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

use Illuminate\Http\Request;
use App\Models\RentalAgreement;
use App\Models\Property;
use App\Models\Room;
use Imagick;


class RentalAgreementController extends Controller
{
    public function index()
    {
        $rentalAgreements = RentalAgreement::all();

        // foreach ($rentalAgreements as $agreement) {
        //     Log::info('Stored Signature:', ['signature' => $agreement->signature_svg_string]);
        // }

        if ($rentalAgreements->isEmpty()) {
            return $this->errorResponse(null, "no rental agreements found");
        }

    
        return $this->successResponse(
            ['rentalAgreements' => $rentalAgreements], 
            "Rental agreements successfully fetched"
        );
    }
    

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'reservation_id' => 'required|exists:inquiries,id', 
            'rent_start_date' => 'required|date',  
            'rent_end_date' => 'nullable|date',  
            'person_count' => 'required|integer|min:1',
            'total_monthly_due' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'signature_png_string' => 'required|file|mimes:png', // File validation
        ]);
        
        // Generate agreement_code (format: agreement-XXXXXX)
        $agreementCode = 'agreement-' . mt_rand(100000, 999999);
        
        // Define the custom directory path
        $directory = public_path('storage/contract_signatures');
        
        // Ensure the directory exists
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);  // Create directory if it doesn't exist
        }
    
        // Handle the file upload
        $signaturePath = $request->file('signature_png_string')->storeAs('contract_signatures', $agreementCode . '.png', 'public');
    
        // Save the agreement_code and the file path to the model
        $validatedData['agreement_code'] = $agreementCode;
        $validatedData['signature_png_string'] = $signaturePath;
        $validatedData['status'] = 'active';
        
        // Create Rental Agreement
        $rentalAgreement = RentalAgreement::create($validatedData);
        
        // Generate PDF in the background 
        $this->generatePdfContract($rentalAgreement);
        
        // Return success response immediately
        return $this->successResponse(
            ['rentalAgreement' => $rentalAgreement], 
            "Rental agreement created successfully"
        );
    }
        
    public function generatePdfContract(RentalAgreement $rentalAgreement)
    {
        // Ensure the storage directory exists
        $directory = storage_path("app/public/rental_agreement_contracts");
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
            Log::info("Created directory: " . $directory);
        } else {
            Log::info("Directory already exists: " . $directory);
        } 
    
        // Convert SVG to PNG
        $signatureSvg = $rentalAgreement->signature_svg_string[0]['svg'] ?? null;
        if (!$signatureSvg) {
            Log::error('No SVG data found for rental agreement ID: ' . $rentalAgreement->id);
            return response()->json(['error' => 'No signature SVG found'], 400);
        }
    
        // Define PNG path
        $pngPath = storage_path("app/public/rental_agreement_contracts/{$rentalAgreement->agreement_code}.png");
        
        $conversionSuccess = $this->convertSvgToPng($signatureSvg, $pngPath);

        if (!$conversionSuccess || !file_exists($pngPath)) {
            Log::error('PNG conversion failed: ' . $pngPath);
            return response()->json(['error' => 'Failed to convert SVG to PNG'], 500);
        }
    
        if (!file_exists($pngPath)) {
            Log::error('PNG conversion failed: ' . $pngPath);
            return response()->json(['error' => 'Failed to convert SVG to PNG'], 500);
        }
    
        // Define PDF storage path
        $pdfPath = storage_path("app/public/rental_agreement_contracts/{$rentalAgreement->agreement_code}.pdf");
        Log::info("Attempting to save PDF to: " . $pdfPath);
    
        // Generate the PDF
        try {
            $pdf = Pdf::loadView('rental_agreements.pdf', [
                'rentalAgreement' => $rentalAgreement,
                'signatureImage' => asset("storage/rental_agreement_contracts/{$rentalAgreement->agreement_code}.png")
            ]);
    
            $pdf->save($pdfPath);

            if (!file_exists($pdfPath)) {
                Log::error("PDF file was not created at: " . $pdfPath);
                return response()->json(['error' => 'Failed to generate PDF'], 500);
            }
    
            Log::info("PDF successfully created at: " . $pdfPath);
    
            return response()->json(['pdf_path' => asset("storage/rental_agreement_contracts/{$rentalAgreement->agreement_code}.pdf")], 200);
        } catch (\Exception $e) {
            Log::error("PDF generation error: " . $e->getMessage());
            return response()->json(['error' => 'PDF generation failed'], 500);
        }
    }

    public function downloadPdf($id)
    {
        $rentalAgreement = RentalAgreement::findOrFail($id);
        $pdfPath = storage_path("app/public/rental_agreement_contracts/{$rentalAgreement->agreement_code}.pdf");

        if (!file_exists($pdfPath)) {
            return response()->json(['error' => 'PDF not found'], 404);
        }

        return response()->download($pdfPath);
    }

    public function show($id)
    {
        $rentalAgreement = RentalAgreement::findOrFail($id);
        
        // Append PDF URL only when fetching a single agreement
        $rentalAgreement->pdf_url = asset("storage/rental_agreement_contracts/{$rentalAgreement->agreement_code}.pdf");
    
        return $this->successResponse(
            ['rentalAgreement' => $rentalAgreement], 
            "Rental agreement retrieved successfully"
        );
    }

    
    
}
