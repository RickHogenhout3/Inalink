<?php
return [
    'ai_provider' => 'ollama',
    'ollama_base_url' => 'http://127.0.0.1:11434',
    // Snelle standaard voor een vloeiende chat op de meeste computers.
    'ollama_model' => 'qwen3:1.7b',

    // Optional OpenAI settings. Leave empty while using Ollama.
    'openai_api_key' => '',
    'openai_base_url' => 'https://api.openai.com/v1',
    'openai_model' => 'gpt-5-mini',
];
