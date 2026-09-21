<?php

// Copy this file to config.local.php.
// config.local.php is ignored by Git.
return [
    // FREE LOCAL DEFAULT
    'ai_provider' => 'ollama',
    'ollama_base_url' => 'http://127.0.0.1:11434',
    // Snelle standaard voor een vloeiende chat op de meeste computers.
    'ollama_model' => 'qwen3:1.7b',

    // OPTIONAL: only needed if you later switch ai_provider to 'openai'.
    'openai_api_key' => '',
    'openai_base_url' => 'https://api.openai.com/v1',
    'openai_model' => 'gpt-5-mini',
];
