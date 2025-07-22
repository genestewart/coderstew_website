<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InquiryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Allow all users to submit contact forms
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:100',
                'regex:/^[a-zA-Z\s\-\'\.]+$/', // Only letters, spaces, hyphens, apostrophes, dots
            ],
            'email' => [
                'required',
                'email:rfc,dns',
                'max:255',
                'not_regex:/\+.*\+/', // Block emails with multiple plus signs (common spam pattern)
            ],
            'message' => [
                'required',
                'string',
                'min:10',
                'max:2000',
            ],
            'subject' => [
                'nullable',
                'string',
                'max:200',
            ],
            // Honeypot field - should always be empty
            'website' => [
                'nullable',
                'max:0', // Must be empty
            ],
            // Timestamp field to prevent too-fast submissions
            'form_start_time' => [
                'required',
                'integer',
                'min:' . (time() - 3600), // Not older than 1 hour
                'max:' . (time() - 3), // At least 3 seconds ago
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Please enter your name.',
            'name.min' => 'Your name must be at least 2 characters.',
            'name.max' => 'Your name may not be longer than 100 characters.',
            'name.regex' => 'Your name contains invalid characters. Please use only letters, spaces, hyphens, apostrophes, and periods.',
            
            'email.required' => 'Please enter your email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Your email address may not be longer than 255 characters.',
            'email.not_regex' => 'The email format appears invalid.',
            
            'message.required' => 'Please enter your message.',
            'message.min' => 'Your message must be at least 10 characters.',
            'message.max' => 'Your message may not be longer than 2000 characters.',
            
            'subject.max' => 'The subject may not be longer than 200 characters.',
            
            'website.max' => 'Please leave this field empty.',
            'form_start_time.required' => 'Form submission error. Please refresh and try again.',
            'form_start_time.min' => 'Form submission error. Please refresh and try again.',
            'form_start_time.max' => 'Please take a moment to review your message before submitting.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'form_start_time' => 'form timing',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from text fields
        $this->merge([
            'name' => $this->input('name') ? trim($this->input('name')) : null,
            'email' => $this->input('email') ? trim(strtolower($this->input('email'))) : null,
            'message' => $this->input('message') ? trim($this->input('message')) : null,
            'subject' => $this->input('subject') ? trim($this->input('subject')) : null,
        ]);

        // Add form start time if not provided (for backward compatibility)
        if (!$this->has('form_start_time')) {
            $this->merge([
                'form_start_time' => time() - 5, // Assume 5 seconds ago
            ]);
        }
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $this->validateSpamContent($validator);
            $this->validateEmailProvider($validator);
            $this->validateSuspiciousPatterns($validator);
        });
    }

    /**
     * Validate against spam content patterns.
     */
    protected function validateSpamContent(Validator $validator): void
    {
        $message = strtolower($this->input('message', ''));
        $name = strtolower($this->input('name', ''));
        $email = strtolower($this->input('email', ''));
        $combinedText = $message . ' ' . $name . ' ' . $email;

        // Common spam keywords
        $spamKeywords = [
            'viagra', 'cialis', 'pharmacy', 'casino', 'poker', 'loan', 'mortgage',
            'seo service', 'link building', 'buy now', 'click here', 'make money',
            'work from home', 'earn money', 'bitcoin', 'cryptocurrency', 'forex',
            'investment', 'binary option', 'dating', 'adult', 'porn', 'xxx',
            'free trial', 'limited time', 'act now', 'urgent', 'congratulations',
            'winner', 'selected', 'claim', 'inheritance', 'prince', 'lottery',
            'million dollar', '$$$', 'no cost', 'risk free', 'guarantee',
            'hacking', 'hack', 'database', 'cheap', 'discount', 'sale',
        ];

        foreach ($spamKeywords as $keyword) {
            if (str_contains($combinedText, $keyword)) {
                $validator->errors()->add('message', 'Your message appears to contain spam content. Please revise and try again.');
                break;
            }
        }

        // Check for excessive URLs
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $message);
        if ($urlCount > 2) {
            $validator->errors()->add('message', 'Please limit the number of links in your message.');
        }

        // Check for repeated characters (common spam pattern)
        if (preg_match('/(.)\1{4,}/', $message)) {
            $validator->errors()->add('message', 'Your message contains suspicious patterns. Please revise and try again.');
        }

        // Check for excessive capitalization
        $upperCaseRatio = strlen(preg_replace('/[^A-Z]/', '', $message)) / max(strlen($message), 1);
        if ($upperCaseRatio > 0.5 && strlen($message) > 20) {
            $validator->errors()->add('message', 'Please reduce the use of capital letters in your message.');
        }
    }

    /**
     * Validate email provider against known spam domains.
     */
    protected function validateEmailProvider(Validator $validator): void
    {
        $email = $this->input('email', '');
        if (!$email) {
            return;
        }

        $domain = strtolower(substr(strrchr($email, '@'), 1));
        
        // Common temporary/disposable email providers
        $disposableDomains = [
            '10minutemail.com', 'tempmail.org', 'guerrillamail.com', 'mailinator.com',
            'yopmail.com', 'temp-mail.org', 'throwaway.email', 'getnada.com',
            'maildrop.cc', 'tempr.email', 'dispostable.com', 'trashmail.com',
        ];

        if (in_array($domain, $disposableDomains)) {
            $validator->errors()->add('email', 'Please use a permanent email address.');
        }

        // Check for suspicious domain patterns
        if (preg_match('/^[0-9]+\.[a-z]{2,3}$/', $domain) || // Numeric domains
            strlen($domain) > 50 || // Very long domains
            substr_count($domain, '.') > 3) { // Too many subdomains
            $validator->errors()->add('email', 'Please verify your email address.');
        }
    }

    /**
     * Validate against suspicious submission patterns.
     */
    protected function validateSuspiciousPatterns(Validator $validator): void
    {
        $name = $this->input('name', '');
        $email = $this->input('email', '');
        $message = $this->input('message', '');

        // Check if name and email are too similar (bot pattern)
        if ($name && $email) {
            $namePart = strtolower(substr($email, 0, strpos($email, '@')));
            $nameClean = strtolower(preg_replace('/[^a-z]/', '', $name));
            if (strlen($nameClean) > 3 && str_contains($namePart, $nameClean)) {
                // This is actually common, so we won't reject it, just note it
            }
        }

        // Check for message that's too similar to name/email (copy-paste pattern)
        if ($message && $name) {
            similar_text(strtolower($message), strtolower($name), $similarity);
            if ($similarity > 80 && strlen($message) < 50) {
                $validator->errors()->add('message', 'Please provide a more detailed message.');
            }
        }

        // Check for binary/encoded content
        if (preg_match('/[^\x20-\x7E\t\r\n]/', $message)) {
            $validator->errors()->add('message', 'Your message contains unsupported characters. Please use standard text only.');
        }
    }

    /**
     * Get the validated data in a clean format.
     */
    public function getCleanData(): array
    {
        $validated = $this->validated();
        
        // Remove spam protection fields from the data that gets stored
        unset($validated['website'], $validated['form_start_time']);
        
        // Add additional data
        $validated['ip_address'] = $this->ip();
        $validated['user_agent'] = $this->userAgent();
        $validated['submitted_at'] = now();
        
        return $validated;
    }
}