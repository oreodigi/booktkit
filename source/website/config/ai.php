<?php
return [

  'pollinations_secret_key' => env('POLLINATIONS_SECRET_KEY', ''),
  'pollinations_text_model' => env('POLLINATIONS_TEXT_MODEL', 'gemini-fast'),
  'pollinations_image_model' => env('POLLINATIONS_IMAGE_MODEL',  'flux'),

  'openai_api_key' => env('OPENAI_API_KEY', ''),
  'openai_text_model' => env('OPENAI_TEXT_MODEL', 'gpt-4o'),
  'openai_image_model' => env('OPENAI_IMAGE_MODEL', 'dall-e-3'),

  'gemini_api_key' => env('GEMINI_API_KEY', ''),
  'gemini_text_model' => env('GEMINI_TEXT_MODEL', 'gemini-2.0-flash'),
  'gemini_image_model' => env('GEMINI_IMAGE_MODEL', 'imagen-4.0-generat-001'),
];
