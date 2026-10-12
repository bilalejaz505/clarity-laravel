<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use RuntimeException;

class ConversationAiService
{
    public function __construct(private AppSettingsService $settings) {}

    public function analyze(array $body): array
    {
        $s = $this->settings->get();
        $key = $this->settings->secret('openai') ?: config('clarity.openai_key');
        $model = $s['aiModel'] ?: config('clarity.openai_model');
        if (! $key || ! $model) {
            throw new RuntimeException('AI is not configured. Ask the administrator to save a key and model.');
        }
        $schemas = json_decode(file_get_contents(resource_path('data/ai-schema.json')), true);
        $mode = $body['mode'];
        $purpose = $body['purpose'] ?? 'personal';
        $question = $body['question'] ?? '';
        $instruction = $mode === 'analyze' ? "Analyze this excerpt for purpose $purpose. Provide a concise summary, evidence-backed positive/attention/neutral observations, and candidate freelance terms if relevant. Use accepted only when explicit mutual acceptance appears in this excerpt and cite both sides. Mentions alone are unclear; offers are proposed. A due date must be explicitly unambiguous YYYY-MM-DD or empty string. Do not resolve tomorrow or weekdays without date context. Amounts must be explicit; never sum or convert currencies. Currency must be explicit ISO currency or UNKNOWN when ambiguous. Explain interpretations separately from observations. Do not create unsupported aggregate statistics." : ($mode === 'reply' ? "Draft a thoughtful reply for this goal: $question. Avoid manipulation and invented commitments. Return answer as the draft text and evidence IDs for messages informing it." : "Answer this question: $question. You see only a retrieved excerpt, not the whole chat. State when evidence is insufficient. Cite the IDs supporting your answer.");
        $response = Http::withToken($key)->timeout(90)->post('https://api.openai.com/v1/responses', ['model' => $model, 'store' => false, 'instructions' => $schemas['instructions']."\n".$instruction, 'input' => json_encode($body['messages'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'max_output_tokens' => $s['aiMaxOutput'], 'text' => ['format' => ['type' => 'json_schema', 'name' => $mode === 'analyze' ? 'conversation_analysis' : 'conversation_answer', 'strict' => true, 'schema' => $schemas[$mode === 'analyze' ? 'analysis' : 'answer']]]]);
        if (! $response->successful()) {
            throw new RuntimeException($response->status() === 429 ? 'AI provider rate limit reached. Please retry later.' : 'AI provider request failed. Check the saved key, model, and account access.');
        }
        $result = $response->json();
        if (($result['status'] ?? 'completed') !== 'completed') {
            throw new RuntimeException('The AI response was incomplete. Try a smaller excerpt.');
        }
        $text = '';
        foreach ($result['output'] ?? [] as $output) {
            foreach ($output['content'] ?? [] as $content) {
                if (($content['type'] ?? '') === 'refusal') {
                    throw new RuntimeException('The AI provider could not fulfill this request.');
                }
                if (($content['type'] ?? '') === 'output_text') {
                    $text .= $content['text'] ?? '';
                }
            }
        }
        $parsed = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
        $ids = array_column($body['messages'], 'id');
        $valid = function (array $evidence) use ($ids) {
            foreach ($evidence as $id) {
                if (! is_string($id) || ! in_array($id, $ids, true)) {
                    throw new RuntimeException('AI evidence validation failed.');
                }
            }

            return array_values(array_unique($evidence));
        };
        if ($mode !== 'analyze') {
            Validator::make($parsed, ['answer' => 'required|string', 'evidence' => 'present|array', 'evidence.*' => 'required|string'])->validate();
            $parsed['evidence'] = $valid($parsed['evidence']);

            return $parsed;
        }

        Validator::make($parsed, ['summary' => 'required|string', 'insights' => 'present|array|max:200', 'insights.*.title' => 'required|string', 'insights.*.detail' => 'required|string', 'insights.*.kind' => 'required|in:positive,attention,neutral', 'insights.*.interpretation' => 'present|string', 'insights.*.evidence' => 'required|array|min:1', 'terms' => 'present|array|max:200', 'terms.*.title' => 'required|string', 'terms.*.detail' => 'required|string', 'terms.*.category' => 'required|in:scope,payment,deadline,revision,change,other', 'terms.*.status' => 'required|in:proposed,accepted,unclear,rejected', 'terms.*.due' => 'present|string', 'terms.*.amount' => 'present|nullable|numeric|min:0', 'terms.*.currency' => 'required|string', 'terms.*.evidence' => 'required|array|min:1'])->validate();
        foreach ($parsed['insights'] as &$i) {
            $i['id'] = (string) Str::uuid();
            $i['evidence'] = $valid($i['evidence']);
        }unset($i);
        foreach ($parsed['terms'] as &$t) {
            $t['id'] = (string) Str::uuid();
            $t['evidence'] = $valid($t['evidence']);
            $t['due'] = preg_match('/^\d{4}-\d{2}-\d{2}$/', $t['due']) ? $t['due'] : '';
            $t['completed'] = false;
            $t['note'] = '';
        }unset($t);
        $parsed['engine'] = 'ai';
        $parsed['analyzedCount'] = count($body['messages']);

        return ['analysis' => $parsed];
    }
}
