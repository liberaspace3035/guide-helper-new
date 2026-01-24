<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIInputService
{
    protected $apiKey;
    protected $apiUrl = 'https://api.openai.com/v1/chat/completions';

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key') ?? env('OPENAI_API_KEY');
    }

    /**
     * 音声入力テキストを整形
     */
    public function formatVoiceText(string $text): string
    {
        if (empty($this->apiKey)) {
            // APIキーが設定されていない場合は、基本的な整形のみ
            return $this->basicFormat($text);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'あなたは日本語のテキスト整形アシスタントです。音声入力で生成されたテキストを、読みやすく自然な日本語に整形してください。句読点を適切に追加し、誤字脱字を修正してください。ただし、元の意味を変えないように注意してください。',
                    ],
                    [
                        'role' => 'user',
                        'content' => $text,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 500,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                return $result['choices'][0]['message']['content'] ?? $this->basicFormat($text);
            } else {
                Log::warning('OpenAI API エラー', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return $this->basicFormat($text);
            }
        } catch (\Exception $e) {
            Log::error('AI入力補助エラー: ' . $e->getMessage());
            return $this->basicFormat($text);
        }
    }

    /**
     * 基本的な整形（APIキーがない場合）
     */
    protected function basicFormat(string $text): string
    {
        // 基本的な整形処理
        $text = trim($text);
        // 連続する空白を1つに
        $text = preg_replace('/\s+/', ' ', $text);
        // 文末に句点を追加（ない場合）
        if (!preg_match('/[。！？]$/u', $text)) {
            $text .= '。';
        }
        return $text;
    }

    /**
     * 依頼内容の入力補助
     */
    public function assistRequestInput(string $userInput): array
    {
        if (empty($this->apiKey)) {
            return [
                'suggestions' => [],
                'formatted_text' => $this->basicFormat($userInput),
            ];
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiUrl, [
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'あなたは同行援護サービスの依頼内容入力補助アシスタントです。ユーザーの入力内容を分析し、適切な表現に整形する提案をしてください。ただし、自動判断や分析は行わず、あくまで入力補助のみを行ってください。',
                    ],
                    [
                        'role' => 'user',
                        'content' => $userInput,
                    ],
                ],
                'temperature' => 0.3,
                'max_tokens' => 300,
            ]);

            if ($response->successful()) {
                $result = $response->json();
                $formatted = $result['choices'][0]['message']['content'] ?? $this->basicFormat($userInput);
                return [
                    'suggestions' => [],
                    'formatted_text' => $formatted,
                ];
            } else {
                return [
                    'suggestions' => [],
                    'formatted_text' => $this->basicFormat($userInput),
                ];
            }
        } catch (\Exception $e) {
            Log::error('AI入力補助エラー: ' . $e->getMessage());
            return [
                'suggestions' => [],
                'formatted_text' => $this->basicFormat($userInput),
            ];
        }
    }
}




