<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Inquiry extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'message',
        'subject',
        'ip_address',
        'user_agent',
        'submitted_at',
        'status',
        'spam_score',
        'admin_notes',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
        'spam_score' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'spam_score' => 0,
    ];

    /**
     * Scope for non-spam inquiries
     */
    public function scopeNotSpam($query)
    {
        return $query->where('status', '!=', 'spam');
    }

    /**
     * Scope for pending inquiries
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope for recent inquiries
     */
    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Check if inquiry is likely spam based on various factors
     */
    public function calculateSpamScore(): int
    {
        $score = 0;
        $message = strtolower($this->message ?? '');
        $name = strtolower($this->name ?? '');
        $email = strtolower($this->email ?? '');

        // Check for spam keywords
        $spamKeywords = [
            'viagra', 'casino', 'loan', 'seo service', 'bitcoin', 'forex',
            'make money', 'click here', 'buy now', 'free trial', 'guarantee'
        ];

        foreach ($spamKeywords as $keyword) {
            if (str_contains($message, $keyword)) {
                $score += 20;
            }
        }

        // Check for excessive URLs
        $urlCount = preg_match_all('/https?:\/\/[^\s]+/', $this->message ?? '');
        if ($urlCount > 2) {
            $score += 15;
        }

        // Check for repeated characters
        if (preg_match('/(.)\1{4,}/', $this->message ?? '')) {
            $score += 10;
        }

        // Check for excessive capitalization
        if ($this->message) {
            $upperCaseRatio = strlen(preg_replace('/[^A-Z]/', '', $this->message)) / max(strlen($this->message), 1);
            if ($upperCaseRatio > 0.5 && strlen($this->message) > 20) {
                $score += 15;
            }
        }

        // Check for suspicious email patterns
        if ($this->email) {
            $domain = substr(strrchr($this->email, '@'), 1);
            if (preg_match('/^[0-9]+\.[a-z]{2,3}$/', $domain)) {
                $score += 25;
            }
        }

        // Check for very short or very long messages
        $messageLength = strlen($this->message ?? '');
        if ($messageLength < 10) {
            $score += 10;
        } elseif ($messageLength > 1500) {
            $score += 5;
        }

        return min($score, 100); // Cap at 100
    }

    /**
     * Mark inquiry as spam
     */
    public function markAsSpam(string $reason = null): void
    {
        $this->update([
            'status' => 'spam',
            'spam_score' => $this->calculateSpamScore(),
            'admin_notes' => $reason ? "Marked as spam: {$reason}" : 'Marked as spam'
        ]);
    }

    /**
     * Mark inquiry as legitimate
     */
    public function markAsLegitimate(): void
    {
        $this->update([
            'status' => 'reviewed',
            'spam_score' => 0
        ]);
    }

    /**
     * Get status badge color for admin interface
     */
    public function getStatusBadgeAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'reviewed' => 'success',
            'spam' => 'danger',
            'replied' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Get formatted submitted date
     */
    protected function submittedAt(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $value ? \Carbon\Carbon::parse($value) : $this->created_at,
        );
    }

    /**
     * Get short message for display
     */
    public function getShortMessageAttribute(): string
    {
        return \Str::limit($this->message ?? '', 100);
    }

    /**
     * Check if inquiry needs attention (high spam score but not marked as spam)
     */
    public function needsReview(): bool
    {
        return $this->status === 'pending' && $this->calculateSpamScore() > 30;
    }
}
