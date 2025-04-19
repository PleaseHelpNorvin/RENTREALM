<?php

namespace App\Http\Controllers\rest;

use Imagick;
use Carbon\Carbon;
use App\Models\Room;
use App\Models\Property;
use App\Models\UserProfile;
use App\Models\RentalAgreement;
use App\Models\Billing;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;


class RentalAgreementController extends Controller
{
    public function index()
    {
        $rentalAgreements = RentalAgreement::with('pivotTenants.userProfile.user', 'reservation',)->get();

        if ($rentalAgreements->isEmpty()) {
            return $this->errorResponse(null, "no rental agreements found");
        }

        foreach ($rentalAgreements as $agreement) {
            $agreement->signature_png_string = url('storage/' . $agreement->signature_png_string);
        }
        
        return $this->successResponse(
            ['rental_agreements' => $rentalAgreements], 
            "Rental agreements successfully fetched"
        );
    }
    
 // ============================================================================================================================
    public function store(Request $request, )
    {
        Log::info('Rental Agreement Store Function Called', ['request_data' => $request->all()]);

        $validatedData = $request->validate([
            'reservation_id' => 'required|exists:reservations,id', 
            'rent_start_date' => 'required|date',  
            'rent_end_date' => 'nullable|date',  
            'person_count' => 'required|integer|min:1',
            'total_amount' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'signature_png_string' => 'required|file|mimes:png', // File validation
            'is_advance_payment' => 'required|boolean',
        ]);
        $isAdvancePayment = $validatedData['is_advance_payment'];
        if ($isAdvancePayment) {
            $validatedData['rent_start_date'] = Carbon::parse($validatedData['rent_start_date'])->format('Y-m-d');
        }
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

        //forUpdating steps when create success
        $user = $rentalAgreement->reservation->userProfile->user;
        $user->steps = '5';
        $user->save();

        // Generate PDF in the background 
        $this->generatePdfContract($rentalAgreement);
        
        // Return success response immediately
        return $this->successResponse(
            ['rental_agreements' => [$rentalAgreement]], 
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
    
        // Full path to the image
        $imagePath = storage_path("app/public/" . $rentalAgreement->signature_png_string);
    
        // Convert image to Base64
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath); // Get correct MIME type (e.g., image/png)
            $signatureImage = "data:{$mimeType};base64,{$imageData}"; // Create data URI
        } else {
            $signatureImage = null; // Fallback if image is missing
        }
    
        // Generate the PDF
        $pdf = Pdf::setOptions(['isRemoteEnabled' => true]) // Ensure remote images can be loaded
                  ->loadView('rental_agreements.pdf', compact('rentalAgreement', 'signatureImage'))
                  ->setPaper('A4', 'portrait');
    
        return $pdf->download("Rental_Agreement_{$rentalAgreement->agreement_code}.pdf");
    }

    public function show($agreementCode)
    {
        $rentalAgreement = RentalAgreement::where('agreement_code', $agreementCode)->first();

        if (!$rentalAgreement) {
            return response()->json(['message' => 'Rental agreement not found'], 404);
        }
    
        // Append PDF URL only when fetching a single agreement
        $rentalAgreement->pdf_url = asset("storage/rental_agreement_contracts/{$rentalAgreement->agreement_code}.pdf");
    
        return $this->successResponse(
            ['rental_agreements' => [$rentalAgreement]], 
            "Rental agreement retrieved successfully"
        );
    }

    
    // ============================================================================================================================

    public function generatePdfUrl($agreementCode)
    {
        // Find the rental agreement by agreement_code instead of id
        $rentalAgreement = RentalAgreement::where('agreement_code', $agreementCode)->firstOrFail();

        // Full path to the image
        $imagePath = storage_path("app/public/" . $rentalAgreement->signature_png_string);

        // Convert image to Base64
        if (file_exists($imagePath)) {
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);
            $signatureImage = "data:{$mimeType};base64,{$imageData}";
        } else {
            $signatureImage = null;
        }

        // Generate the PDF
        $pdf = Pdf::setOptions(['isRemoteEnabled' => true])
                ->loadView('rental_agreements.pdf', compact('rentalAgreement', 'signatureImage'))
                ->setPaper('A4', 'portrait');

        // Define the storage path
        $fileName = "Rental_Agreement_{$rentalAgreement->agreement_code}.pdf";
        $pdfPath = "public/pdfs/{$fileName}";
        $fullPath = storage_path("app/{$pdfPath}");

        // Ensure directory exists
        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        // Save PDF to storage
        $pdf->save($fullPath);

        // Generate the URL
        $pdfUrl = asset("storage/pdfs/{$fileName}");

        return $this->successResponse(['pdf_url' => $pdfUrl], 'PDF generated successfully');
    }

    // ============================================================================================================================

    public function indexByProfileId($profileId) {
        // Find the profile and eager-load reservations with rental agreements
        $userProfile = UserProfile::with('reservations.rentalAgreement')->find($profileId);
    
        // Check if profile exists
        if (!$userProfile) {
            return $this->notFoundResponse(null, 'User profile not found.');
        }
    
        // Collect all rental agreements from reservations
        $rentalAgreements = $userProfile->reservations->pluck('rentalAgreement')->filter();
    
        // Check if there are rental agreements
        if ($rentalAgreements->isEmpty()) {
            return $this->notFoundResponse(null, 'No rental agreements found for this profile.');
        }
    
        return $this->successResponse(
            ['rental_agreements' => $rentalAgreements],
            "Fetched rental agreements for profile ID: $profileId"
        );
    }

    public function ShowActiveRentalAgreementByProfileId($profileId)
    {
        $activeRentalAgreements = RentalAgreement::whereHas('reservation', function ($query) use ($profileId) {
                $query->where('profile_id', $profileId);
            })
            ->where('status', 'active')
            ->get();
    
        if ($activeRentalAgreements->isEmpty()) {
            return $this->notFoundResponse(null, "No Active Rental Agreement Found for this profile.");
        }
    
        return $this->successResponse(
            ['rental_agreements' => $activeRentalAgreements], 
            "Active Rental For ProfileId: $profileId fetched Successfully"
        );
    }

    public function ViewContractCountdown($agreementId)
    {
        // Find the rental agreement
        $agreement = RentalAgreement::find($agreementId);
        
        if (!$agreement) {
            return $this->notFoundResponse(null, "Agreement with ID $agreementId not found.");
        }

        // Find the latest billing record linked to this rental agreement
        $billing = Billing::where('billable_id', $agreementId)
            ->where('billable_type', RentalAgreement::class)
            ->orderBy('updated_at', 'desc')
            ->first();

        if (!$billing) {
            return $this->notFoundResponse(null, "No billing record found for agreement ID $agreementId.");
        }

        // Get last updated date of the billing
        $lastBillingUpdate = Carbon::parse($billing->updated_at);

        // Calculate the next billing date (1 month after last update)
        $nextBillingDate = $lastBillingUpdate->copy()->addMonth();

        // Calculate remaining days (rounded)
        $remainingDays = Carbon::now()->diffInDays($nextBillingDate, false);
        $remainingDays = max(round($remainingDays), 0); // Ensure non-negative

        return $this->successResponse([
            'rental_agreement_id' => $agreementId,
            'rental_agreement_code' => $agreement->agreement_code, // Agreement code
            'last_billing_update' => $lastBillingUpdate->toDateTimeString(),
            'next_billing_date' => $nextBillingDate->toDateString(),
            'remaining_days' => $remainingDays,
            'billing_status' => $billing->status, // Added billing status
            'total_amount' => $billing->total_amount, // Added total billing amount
            'amount_paid' => $billing->amount_paid, // Added amount paid
            'remaining_balance' => $billing->remaining_balance // Added remaining balance
        ], 'Contract countdown retrieved successfully.');
    }


    
    public function getRoomsByProfileId($profileId)
    {
        $rentalAgreements = RentalAgreement::whereHas('reservation', function ($query) use ($profileId) {
            $query->where('profile_id', $profileId);
        })->with('reservation.room')->get();
    
        if ($rentalAgreements->isEmpty()) {
            return $this->notFoundResponse(null, 'No rooms found for this profile.');
        }
    
        // Extract room details
        $rooms = $rentalAgreements->map(function ($agreement) {
            return [
                'room_id'        => $agreement->reservation->room->id ?? null,
                'room_code'      => $agreement->reservation->room->room_code ?? null,
                'rent_start_date'=> $agreement->rent_start_date,
                'rent_end_date'  => $agreement->rent_end_date,
            ];
        })->unique('room_id')->values();;
    
        return $this->successResponse($rooms, 'Rooms retrieved successfully.');
    }
}
