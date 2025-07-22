<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InquiryRequest;
use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class InquiryController extends Controller
{
    /**
     * Store a new inquiry with spam protection and validation.
     */
    public function store(InquiryRequest $request): JsonResponse
    {
        try {
            // Get clean validated data
            $data = $request->getCleanData();
            
            // Create the inquiry
            $inquiry = Inquiry::create($data);
            
            // Calculate and store spam score
            $spamScore = $inquiry->calculateSpamScore();
            $inquiry->update(['spam_score' => $spamScore]);
            
            // Auto-mark as spam if score is very high
            if ($spamScore >= 70) {
                $inquiry->markAsSpam('High spam score: ' . $spamScore);
                
                // Log spam attempt for monitoring
                Log::warning('High spam score inquiry submitted', [
                    'inquiry_id' => $inquiry->id,
                    'spam_score' => $spamScore,
                    'ip_address' => $request->ip(),
                    'email' => $data['email'] ?? null,
                ]);
                
                // Still return success to avoid giving spammers feedback
                return response()->json([
                    'success' => true,
                    'message' => 'Thank you for your message. We will review it and get back to you soon.',
                ], 201);
            }
            
            // Log successful submission for monitoring
            Log::info('New inquiry submitted', [
                'inquiry_id' => $inquiry->id,
                'spam_score' => $spamScore,
                'status' => $inquiry->status,
                'ip_address' => $request->ip(),
            ]);
            
            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Thank you for your message! We will get back to you within 24 hours.',
                'inquiry_id' => $inquiry->id,
            ], 201);
            
        } catch (\Exception $e) {
            // Log the error for debugging
            Log::error('Failed to create inquiry', [
                'error' => $e->getMessage(),
                'ip_address' => $request->ip(),
                'request_data' => $request->except(['website', 'form_start_time']),
            ]);
            
            // Return generic error message
            return response()->json([
                'success' => false,
                'message' => 'Sorry, there was an error processing your message. Please try again later.',
            ], 500);
        }
    }
    
    /**
     * Get inquiry statistics (for admin dashboard).
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'total' => Inquiry::count(),
                'pending' => Inquiry::pending()->count(),
                'spam' => Inquiry::where('status', 'spam')->count(),
                'recent' => Inquiry::recent(7)->notSpam()->count(),
                'high_spam_score' => Inquiry::where('spam_score', '>', 50)->where('status', '!=', 'spam')->count(),
            ];
            
            return response()->json([
                'success' => true,
                'data' => $stats,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get inquiry stats', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics.',
            ], 500);
        }
    }
}
