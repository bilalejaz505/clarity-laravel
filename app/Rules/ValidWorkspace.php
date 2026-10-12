<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidWorkspace implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        try {
            $this->require(is_array($value), 'Workspace must be an object.');
            $this->checkWorkspace($value);
        } catch (\InvalidArgumentException $e) {
            $fail($e->getMessage());
        }
    }

    private function checkWorkspace(array $store): void
    {
        foreach (['conversations', 'tasks', 'invoices'] as $key) {
            $this->require(is_array($store[$key] ?? null) && array_is_list($store[$key]), "Workspace $key must be an array.");
        }

        $this->require(count($store['conversations']) <= 200, 'A workspace may contain at most 200 conversations.');
        foreach ($store['conversations'] as $conversation) {
            $this->require(is_array($conversation), 'Invalid conversation.');
            $this->strings($conversation, ['id' => 100, 'name' => 200, 'platform' => 100, 'purpose' => 30, 'created' => 100]);
            $this->require(in_array($conversation['purpose'], ['personal', 'freelance'], true), 'Invalid conversation purpose.');
            $this->require(is_bool($conversation['sample'] ?? null), 'Invalid sample flag.');
            $this->require(is_array($conversation['messages'] ?? null) && array_is_list($conversation['messages']) && count($conversation['messages']) <= 100000, 'Invalid message collection or message limit.');
            foreach ($conversation['messages'] as $message) {
                $this->require(is_array($message), 'Invalid message.');
                $this->strings($message, ['id' => 100, 'sender' => 200, 'text' => 100000, 'source' => 1000]);
                $timestamp = $message['timestamp'] ?? null;
                $this->require($timestamp === null || (is_string($timestamp) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(?:\.\d+)?Z$/', $timestamp) && strtotime($timestamp) !== false), 'Invalid message timestamp.');
            }

            $this->require(array_key_exists('analysis', $conversation), 'Missing conversation analysis.');
            if (isset($conversation['analysis'])) {
                $analysis = $conversation['analysis'];
                $this->require(is_array($analysis) && in_array($analysis['engine'] ?? null, ['local', 'ai'], true), 'Invalid analysis.');
                $this->strings($analysis, ['summary' => 100000]);
                $this->require(is_numeric($analysis['analyzedCount'] ?? null) && is_array($analysis['insights'] ?? null) && is_array($analysis['terms'] ?? null), 'Invalid analysis collections.');
                foreach ($analysis['insights'] as $insight) {
                    $this->require(is_array($insight), 'Invalid insight.');
                    $this->strings($insight, ['id' => 100, 'title' => 1000, 'detail' => 100000, 'interpretation' => 100000]);
                    $this->require(in_array($insight['kind'] ?? null, ['positive', 'attention', 'neutral'], true), 'Invalid insight kind.');
                    $this->evidence($insight);
                }

                foreach ($analysis['terms'] as $term) {
                    $this->require(is_array($term), 'Invalid contract term.');
                    $this->strings($term, ['id' => 100, 'category' => 30, 'title' => 1000, 'detail' => 100000, 'status' => 30, 'due' => 100, 'currency' => 20, 'note' => 100000]);
                    $this->require(in_array($term['category'], ['scope', 'payment', 'deadline', 'revision', 'change', 'other'], true) && in_array($term['status'], ['proposed', 'accepted', 'unclear', 'rejected'], true), 'Invalid term status/category.');
                    $this->require(is_bool($term['completed'] ?? null) && (($term['amount'] ?? null) === null || is_numeric($term['amount']) && $term['amount'] >= 0), 'Invalid term amount/completion.');
                    $this->evidence($term);
                }
            }
        }

        foreach ($store['tasks'] as $task) {
            $this->require(is_array($task), 'Invalid task.');
            $this->strings($task, ['id' => 100, 'title' => 200, 'due' => 100, 'status' => 30, 'conversationId' => 100, 'note' => 100000]);
            $this->require(in_array($task['status'], ['todo', 'doing', 'done'], true), 'Invalid task status.');
        }

        foreach ($store['invoices'] as $invoice) {
            $this->require(is_array($invoice), 'Invalid invoice.');
            $this->strings($invoice, ['id' => 100, 'conversationId' => 100, 'client' => 200, 'description' => 100000, 'currency' => 20, 'due' => 100, 'status' => 30]);
            $this->require(is_numeric($invoice['amount'] ?? null) && $invoice['amount'] >= 0 && in_array($invoice['status'], ['draft', 'sent', 'paid'], true), 'Invalid invoice amount/status.');
        }

        $this->require(! isset($store['reviews']) || (is_array($store['reviews']) && array_is_list($store['reviews'])), 'Invalid review collection.');
        foreach ($store['reviews'] ?? [] as $review) {
            $this->require(is_array($review), 'Invalid review.');
            $this->strings($review, ['id' => 100, 'conversationId' => 100, 'termTitle' => 1000, 'from' => 100000, 'to' => 100000, 'at' => 100]);
        }
    }

    private function evidence(array $record): void
    {
        $this->require(is_array($record['evidence'] ?? null), 'Evidence must be an array.');
        foreach ($record['evidence'] as $id) {
            $this->require(is_string($id) && strlen($id) <= 100, 'Invalid evidence reference.');
        }
    }

    private function strings(array $record, array $limits): void
    {
        foreach ($limits as $key => $limit) {
            $this->require(is_string($record[$key] ?? null) && mb_strlen($record[$key]) <= $limit, "Invalid workspace field: $key.");
        }
    }

    private function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new \InvalidArgumentException($message);
        }
    }
}
