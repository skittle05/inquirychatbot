<?php
// AI Configuration Settings
define('OPENAI_API_KEY', 'your-api-key-here'); // Replace with your actual OpenAI API key
define('AI_MODEL', 'gpt-3.5-turbo'); // You can change this to gpt-4 or other models
define('MAX_TOKENS', 1000);
define('TEMPERATURE', 0.7);

// Error messages
define('ERROR_API_KEY_MISSING', 'OpenAI API key is not configured.');
define('ERROR_INVALID_REQUEST', 'Invalid request parameters.');
define('ERROR_API_REQUEST', 'Error occurred while processing the AI request.');
