<?php

namespace App\Services\Ai;

use App\Models\Language;
use App\Models\OrganizerAiBalance;
use Illuminate\Http\Request;

class AiContentService
{
    // Allowed SEO fields for item_seo mode.
    public const ALLOWED_FIELDS = [
        'title',
        'summary',
        'description',
        'meta_keywords',
        'meta_description',
        'category_section_title',
    ];

    // Load user language targets (optionally filtered by a single language code).
    public function getTargets(int $userId, ?string $langCode): array
    {
        $query = Language::query();
        if ($langCode) {
            $query->where('code', $langCode);
        }

        return $query
            ->get()
            ->map(fn($l) => ['code' => $l->code, 'name' => $l->name])
            ->values()
            ->all();
    }

    // Build prompts based on mode and engine (batch prompt for pollinations).
    public function buildPrompts(
        string $mode,
        string $baseIdea,
        Request $request,
        array $targets,
        array $targetLangCodes,
        string $engine,
        ContentGenerator $generator
    ): array {
        if ($mode === 'home_page_text') {
            $field = $this->sanitizeHomeField((string) $request->input('field', ''));
            if ($field === '') {
                return [];
            }

            $langLabel = $this->resolveHomeLanguageLabel($request, $targets);
            return [$this->buildHomePageFieldPrompt($baseIdea, $field, $langLabel)];
        }

        if ($mode === 'mail') {
            $field = $this->sanitizeMailField((string) $request->input('field', ''));
            if ($field === '') {
                return [];
            }

            $langLabel = $this->resolveHomeLanguageLabel($request, $targets);
            return [$this->buildMailFieldPrompt($baseIdea, $field, $langLabel)];
        }

        if ($mode === 'additional_section') {
            $field = $this->sanitizeHomeField((string) $request->input('field', ''));
            $langLabel = $this->resolveHomeLanguageLabel($request, $targets);

            if ($field !== '') {
                return [$this->buildAdditionalSectionPrompt($baseIdea, $field, $langLabel)];
            }

            $prompts = [];
            foreach ($targets as $t) {
                $code = trim((string) ($t['code'] ?? ''));
                if ($code === '') {
                    continue;
                }

                $name = trim((string) ($t['name'] ?? ''));
                $label = $name !== '' ? "{$code} ({$name})" : $code;

                $prompts[] = $this->buildAdditionalSectionPrompt($baseIdea, "{$code}_section_name", $label);
                $prompts[] = $this->buildAdditionalSectionPrompt($baseIdea, "{$code}_content", $label);
            }

            return $prompts;
        }

        if ($mode === 'subcategory') {
            $cat = trim((string) $request->input('category_name', ''));
            $idea =
                "Generate a SUBCATEGORY name under category \"{$cat}\".\n" .
                "Rules: Do NOT repeat category name.\n" .
                "Idea: {$baseIdea}";
            return [$generator->buildPrompt($idea, $targets)];
        }

        if ($mode === 'faq') {
            $langLabel = $this->resolveHomeLanguageLabel($request, $targets);
            return [$this->buildFaqPrompt($baseIdea, $langLabel)];
        }

        if ($mode === 'page') {
            $requestedField = trim((string) $request->input('field', ''));
            if ($requestedField !== '') {
                $langLabel = $this->resolvePageLanguageLabel($request, $targets, $requestedField);
                return [$this->buildPageSingleFieldPrompt($baseIdea, $langLabel, $requestedField)];
            }

            $prompts = [];
            foreach ($targets as $t) {
                $code = trim((string) ($t['code'] ?? ''));
                if ($code === '') continue;

                $name = trim((string) ($t['name'] ?? ''));
                $label = $name !== '' ? "{$code} ({$name})" : $code;

                $prompts[] = $this->buildPagePromptWithKeys(
                    $baseIdea,
                    $label,
                    "{$code}_title",
                    "{$code}_body"
                );
            }

            return $prompts;
        }

        if ($mode === 'category') {
            $idea =
                "Generate a CATEGORY name.\n" .
                "Idea: {$baseIdea}";
            return [$generator->buildPrompt($idea, $targets)];
        }

        if ($mode === 'item_seo') {
            $cat = trim((string) $request->input('category_name', ''));
            $sub = trim((string) $request->input('subcategory_name', ''));

            $requestedField = (string) $request->input('field', '');
            $requestedLang = (string) $request->input('lang', '');

            $fields = in_array($requestedField, self::ALLOWED_FIELDS, true)
                ? [$requestedField]
                : self::ALLOWED_FIELDS;

            $langCodes = (is_string($requestedLang) && in_array($requestedLang, $targetLangCodes, true))
                ? [$requestedLang]
                : $targetLangCodes;

            // Pollinations tends to fail on many sequential calls, so use one batch prompt.
            $useBatchPrompt = $engine === 'pollinations'
                && (count($fields) > 1 || count($langCodes) > 1);

            if ($useBatchPrompt) {
                return [$this->buildItemSeoBatchPrompt(
                    baseIdea: $baseIdea,
                    category: $cat,
                    subcategory: $sub,
                    fields: $fields,
                    langCodes: $langCodes
                )];
            }

            $ideas = $this->buildItemSeoIdeas(
                baseIdea: $baseIdea,
                category: $cat,
                subcategory: $sub,
                targets: $targets,
                field: $request->input('field'),
                lang: $request->input('lang')
            );

            return array_map(fn($i) => (string) ($i['idea'] ?? ''), $ideas);
        }

        return [$generator->buildPrompt($baseIdea, $targets)];
    }

    // Execute one or many prompts and merge the JSON objects.
    public function runPrompts(
        array $prompts,
        AiTextManager $aiText,
        string $engine,
        ContentGenerator $generator,
        ?OrganizerAiBalance $membership = null,
        ?AiTokenUsageService $tokenUsage = null
    ): array {
        $json = [];
        $totalTokens = 0;
        $trackTokens = $membership && $tokenUsage && $tokenUsage->shouldCount($engine);

        foreach ($prompts as $prompt) {
            $promptText = trim((string) $prompt);
            if ($promptText === '') {
                continue;
            }

            $resp = $aiText->generateWithMeta($promptText, $engine);
            $rawText = (string) ($resp['text'] ?? '');

            if ($trackTokens) {
                $usageTokens = $tokenUsage->totalFromUsage($resp['usage'] ?? null);
                $totalTokens += $usageTokens ?? $tokenUsage->estimatePromptAndResponseTokens($promptText, $rawText);
            }

            $part = $generator->extractJson($rawText);
            if (is_array($part) && $part) {
                $json = array_merge($json, $part);
            }
        }

        if ($trackTokens && $totalTokens > 0) {
            $tokenUsage->recordUsage($membership, $engine, $totalTokens);
        }

        return $json;
    }

    // Build per-field strict JSON prompts (one key per request)
    private function buildItemSeoIdeas(
        string $baseIdea,
        string $category,
        string $subcategory,
        array $targets,
        ?string $field = null,
        ?string $lang = null
    ): array {
        $fields = ($field && in_array($field, self::ALLOWED_FIELDS, true))
            ? [$field]
            : self::ALLOWED_FIELDS;

        $langCodes = array_map(fn($t) => $t['code'], $targets);
        $langs = ($lang && in_array($lang, $langCodes, true))
            ? [$lang]
            : $langCodes;

        $fieldRules = [
            'title' => [
                'rule' => 'title MUST be short (max 60 characters).',
                'hint' => 'Short product title',
            ],
            'summary' => [
                'rule' => 'summary MUST be short (3-4 sentences, plain text).',
                'hint' => 'Brief product overview',
            ],
            'description' => [
                'rule' => 'description MUST be detailed HTML and AT LEAST 1000 characters long.',
                'hint' => '<p>Detailed product description...</p>',
            ],
            'meta_keywords' => [
                'rule' => 'meta_keywords MUST be comma-separated keywords (no sentences).',
                'hint' => 'keyword1, keyword2, keyword3',
            ],
            'meta_description' => [
                'rule' => 'meta_description MUST be unique (max 160 characters).',
                'hint' => 'SEO meta description',
            ],
        ];

        $ideas = [];
        foreach ($langs as $code) {
            foreach ($fields as $f) {
                $key = "{$code}_{$f}";
                $rule = $fieldRules[$f]['rule'] ?? '';
                $hint = $fieldRules[$f]['hint'] ?? '...';

                $ideas[] = [
                    'lang'  => $code,
                    'field' => $f,
                    'key'   => $key,
                    'idea'  =>
                        "SYSTEM: You are a professional eCommerce SEO copywriter.\n" .
                        "RESPOND WITH PURE JSON ONLY. NO markdown. NO explanation.\n\n" .
                        "Category: {$category}\n" .
                        "Subcategory: {$subcategory}\n" .
                        "Base Idea: {$baseIdea}\n\n" .
                        "Generate ONLY one key.\n" .
                        "- Language: {$code}\n" .
                        "- Field: {$f}\n\n" .
                        "STRICT RULES:\n" .
                        "1) Output ONLY valid JSON.\n" .
                        "2) Generate ONLY this key: {$key}\n" .
                        "3) No extra keys.\n" .
                        "4) Content must be unique and SEO-friendly.\n" .
                        ($rule ? "5) {$rule}\n" : '') .
                        "\nJSON FORMAT (EXACT):\n" .
                        "{\n" .
                        "  \"{$key}\": \"{$hint}\"\n" .
                        "}",
                ];
            }
        }

        return $ideas;
    }

    // Build a single strict JSON prompt covering multiple fields/languages
    private function buildItemSeoBatchPrompt(
        string $baseIdea,
        string $category,
        string $subcategory,
        array $fields,
        array $langCodes
    ): string {
        $fieldList = implode(', ', $fields);
        $langList = implode(', ', $langCodes);

        $ruleMap = [
            'title' => 'title MUST be short (max 60 characters).',
            'summary' => 'summary MUST be short (3-4 sentences, plain text).',
            'description' => 'description MUST be detailed HTML and AT LEAST 1000 characters long.',
            'meta_keywords' => 'meta_keywords MUST be comma-separated keywords (no sentences).',
            'meta_description' => 'meta_description MUST be unique (max 160 characters).',
        ];

        $rules = "Rules:\n";
        foreach ($fields as $f) {
            if (isset($ruleMap[$f])) {
                $rules .= "- {$f}: {$ruleMap[$f]}\n";
            }
        }

        return
            "Return ONLY valid JSON. No markdown. No explanation.\n" .
            "Category: {$category}\n" .
            "Subcategory: {$subcategory}\n" .
            "Base Idea: {$baseIdea}\n\n" .
            "Generate ONLY these language codes: {$langList}\n" .
            "Generate ONLY these fields: {$fieldList}\n" .
            "Keys must be flat: {lang}_{field}\n" .
            $rules . "\n" .
            "Output example:\n" .
            "{\"en_title\":\"...\",\"en_summary\":\"...\"}\n";
    }

    // Validate and normalize a single home page field key.
    public function sanitizeHomeField(string $field): string
    {
        $field = trim($field);
        if ($field === '' || strlen($field) > 80) {
            return '';
        }

        if (!preg_match('/^[a-zA-Z0-9_]+$/', $field)) {
            return '';
        }

        return $field;
    }

    // Validate and normalize a mail field key.
    public function sanitizeMailField(string $field): string
    {
        $field = trim($field);
        if ($field === '') {
            return '';
        }

        return in_array($field, ['subject', 'message'], true) ? $field : '';
    }

    private function buildHomePageFieldPrompt(string $baseIdea, string $field, string $langLabel): string
    {
        $context = $this->homeFieldContext($field);
        $rule = $this->homeFieldRule($field);
        $langLine = $langLabel !== '' ? "Language: {$langLabel}\n" : '';

        return
            "Return ONLY valid JSON. No markdown. No explanation.\n" .
            "You are writing home page section text for an eCommerce site.\n" .
            $langLine .
            ($context !== '' ? "Context: {$context}\n" : '') .
            "Base Idea: {$baseIdea}\n\n" .
            "Generate ONLY this key: {$field}\n" .
            "Rules:\n" .
            "1) Must be directly related to the field/context.\n" .
            "2) Avoid product names, brand names, or unrelated phrases.\n" .
            "3) {$rule}\n" .
            "4) Write in the specified language.\n" .
            "JSON FORMAT (EXACT):\n" .
            "{\n" .
            "  \"{$field}\": \"...\"\n" .
            "}\n";
    }

    private function buildMailFieldPrompt(string $baseIdea, string $field, string $langLabel): string
    {
        $context = $this->mailFieldContext($field);
        $rule = $this->mailFieldRule($field);
        $langLine = $langLabel !== '' ? "Language: {$langLabel}\n" : '';

        return
            "Return ONLY valid JSON. No markdown. No explanation.\n" .
            "You are writing an email to subscribers for an eCommerce site.\n" .
            $langLine .
            ($context !== '' ? "Context: {$context}\n" : '') .
            "Base Idea: {$baseIdea}\n\n" .
            "Generate ONLY this key: {$field}\n" .
            "Rules:\n" .
            "1) Must be directly related to the field/context.\n" .
            "2) Avoid product names if not provided.\n" .
            "3) {$rule}\n" .
            "4) Write in the specified language.\n" .
            "JSON FORMAT (EXACT):\n" .
            "{\n" .
            "  \"{$field}\": \"...\"\n" .
            "}\n";
    }

    private function buildAdditionalSectionPrompt(string $baseIdea, string $field, string $langLabel): string
    {
        $context = $this->additionalSectionContext($field);
        $rule = $this->additionalSectionRule($field);
        $langLine = $langLabel !== '' ? "Language: {$langLabel}\n" : '';

        return
            "Return ONLY valid JSON. No markdown. No explanation.\n" .
            "You are writing additional home page section text for an eCommerce site.\n" .
            $langLine .
            ($context !== '' ? "Context: {$context}\n" : '') .
            "Base Idea: {$baseIdea}\n\n" .
            "Generate ONLY this key: {$field}\n" .
            "Rules:\n" .
            "1) Must be directly related to the field/context.\n" .
            "2) Avoid product names, brand names, or unrelated phrases.\n" .
            "3) {$rule}\n" .
            "4) Write in the specified language.\n" .
            "JSON FORMAT (EXACT):\n" .
            "{\n" .
            "  \"{$field}\": \"...\"\n" .
            "}\n";
    }

    private function buildFaqPrompt(string $baseIdea, string $langLabel): string
    {
        $langLine = $langLabel !== '' ? "Language: {$langLabel}\n" : '';

        return
            "Return ONLY valid JSON. No markdown. No explanation.\n" .
            "You are writing an FAQ for an eCommerce site.\n" .
            $langLine .
            "Base Idea: {$baseIdea}\n\n" .
            "Generate ONLY these keys: question, answer\n" .
            "Rules:\n" .
            "1) question must be clear and concise (max 120 characters).\n" .
            "2) answer must be helpful (2-4 sentences).\n" .
            "3) Write in the specified language.\n" .
            "JSON FORMAT (EXACT):\n" .
            "{\n" .
            "  \"question\": \"...\",\n" .
            "  \"answer\": \"...\"\n" .
            "}\n";
    }

    private function buildPagePromptWithKeys(string $baseIdea, string $langLabel, string $titleKey, string $bodyKey): string
    {
        $langLine = $langLabel !== '' ? "Language: {$langLabel}\n" : '';

        return
            "Return ONLY valid JSON. No markdown. No explanation.\n" .
            "You are writing a custom page for an eCommerce site.\n" .
            $langLine .
            "Base Idea: {$baseIdea}\n\n" .
            "Generate ONLY these keys: {$titleKey}, {$bodyKey}\n" .
            "Rules:\n" .
            "1) title must be short (max 60 characters).\n" .
            "2) body must be detailed and long: 15-20 lines (roughly 150-250 words), at least 900 characters.\n" .
            "3) Use basic HTML like <p> and <ul> if needed.\n" .
            "4) Write in the specified language.\n" .
            "JSON FORMAT (EXACT):\n" .
            "{\n" .
            "  \"{$titleKey}\": \"...\",\n" .
            "  \"{$bodyKey}\": \"...\"\n" .
            "}\n";
    }

    private function buildPageSingleFieldPrompt(string $baseIdea, string $langLabel, string $fieldKey): string
    {
        $langLine = $langLabel !== '' ? "Language: {$langLabel}\n" : '';

        $isBody = str_ends_with($fieldKey, '_body') || $fieldKey === 'body';
        $isTitle = str_ends_with($fieldKey, '_title') || $fieldKey === 'title';

        $rule = $isBody
            ? 'body must be detailed and long: 15-20 lines (roughly 150-250 words), at least 900 characters. Use basic HTML like <p> and <ul> if needed.'
            : 'title must be short (max 60 characters).';

        $context = $isBody ? 'Page body content' : ($isTitle ? 'Page title' : 'Page content');

        return
            "Return ONLY valid JSON. No markdown. No explanation.\n" .
            "You are writing a custom page for an eCommerce site.\n" .
            $langLine .
            "Context: {$context}\n" .
            "Base Idea: {$baseIdea}\n\n" .
            "Generate ONLY this key: {$fieldKey}\n" .
            "Rules:\n" .
            "1) {$rule}\n" .
            "2) Write in the specified language.\n" .
            "JSON FORMAT (EXACT):\n" .
            "{\n" .
            "  \"{$fieldKey}\": \"...\"\n" .
            "}\n";
    }

    private function resolvePageLanguageLabel(Request $request, array $targets, string $field): string
    {
        $langCode = trim((string) $request->input('lang', ''));
        if ($langCode !== '') {
            foreach ($targets as $t) {
                if (($t['code'] ?? '') === $langCode) {
                    $name = trim((string) ($t['name'] ?? ''));
                    return $name !== '' ? "{$langCode} ({$name})" : $langCode;
                }
            }
            return $langCode;
        }

        $parts = explode('_', $field, 2);
        if (count($parts) === 2) {
            $code = trim($parts[0]);
            foreach ($targets as $t) {
                if (($t['code'] ?? '') === $code) {
                    $name = trim((string) ($t['name'] ?? ''));
                    return $name !== '' ? "{$code} ({$name})" : $code;
                }
            }
        }

        return '';
    }

    private function resolveHomeLanguageLabel(Request $request, array $targets): string
    {
        // Prefer explicit lang from request, otherwise first target.
        $langCode = trim((string) $request->input('lang', ''));
        if ($langCode !== '') {
            foreach ($targets as $t) {
                if (($t['code'] ?? '') === $langCode) {
                    $name = trim((string) ($t['name'] ?? ''));
                    return $name !== '' ? "{$langCode} ({$name})" : $langCode;
                }
            }
            return $langCode;
        }

        $first = $targets[0] ?? null;
        if (is_array($first)) {
            $code = trim((string) ($first['code'] ?? ''));
            $name = trim((string) ($first['name'] ?? ''));
            if ($code !== '' && $name !== '') return "{$code} ({$name})";
            return $code !== '' ? $code : $name;
        }

        return '';
    }

    private function homeFieldContext(string $field): string
    {
        $map = [
            'category_section_title' => 'Categories section title',
            'category_section_subtitle' => 'Categories section subtitle',
            'latest_product_section_title' => 'Latest products section title',
            'top_rated_product_section_title' => 'Top rated products section title',
            'top_selling_product_section_title' => 'Top selling products section title',
            'top_rated_product_section_subtitle' => 'Top rated products section subtitle',
            'call_to_action_section_title' => 'Call-to-action headline',
            'call_to_action_section_text' => 'Call-to-action supporting text',
            'call_to_action_section_button_text' => 'Call-to-action button text',
            'call_to_action_section_button_url' => 'Call-to-action button URL',
            'video_section_title' => 'Video section title',
            'video_section_subtitle' => 'Video section subtitle',
            'video_section_text' => 'Video section supporting text',
            'video_section_button_name' => 'Video section button text',
            'video_section_button_url' => 'Video section button URL',
            'video_url' => 'Video URL',
            'tab_section_title' => 'Tabs section title',
            'tab_section_subtitle' => 'Tabs section subtitle',
            'flash_section_title' => 'Flash sale section title',
            'flash_section_subtitle' => 'Flash sale section subtitle',
            'featured_section_title' => 'Featured products section title',
            'features_section_title' => 'Features section title',
        ];

        return $map[$field] ?? 'Home page section text';
    }

    private function mailFieldContext(string $field): string
    {
        if ($field === 'subject') {
            return 'Email subject line for subscribers';
        }
        if ($field === 'message') {
            return 'Email body message for subscribers';
        }
        return 'Subscriber email content';
    }

    private function homeFieldRule(string $field): string
    {
        $f = strtolower($field);

        if (str_contains($f, 'meta_keywords') || str_contains($f, 'meta_keyword')) {
            return 'Comma-separated keywords (no sentences).';
        }

        if (str_contains($f, 'meta_description')) {
            return 'Unique meta description (max 160 characters).';
        }

        if (str_contains($f, 'button_text')) {
            return 'Short CTA (1-3 words).';
        }

        if (str_contains($f, 'button_url') || str_ends_with($f, '_url') || str_contains($f, 'url')) {
            return 'Valid https URL (use https://example.com if unsure).';
        }

        if (str_contains($f, 'subtitle')) {
            return 'Short subtitle (6-12 words).';
        }

        if (str_contains($f, 'title')) {
            return 'Short title (max 60 characters).';
        }

        if (str_contains($f, 'text')) {
            return 'Short supporting text (1-2 sentences).';
        }

        return 'Short, clear label (2-8 words).';
    }

    private function mailFieldRule(string $field): string
    {
        if ($field === 'subject') {
            return 'Short, clear subject (5-12 words, max 70 characters).';
        }
        if ($field === 'message') {
            return 'Write a full email body: 3-6 sentences, 80-160 words, friendly and promotional. Include a soft call-to-action.';
        }
        return 'Write in a clear, friendly tone.';
    }

    private function additionalSectionContext(string $field): string
    {
        $f = strtolower($field);
        if (str_ends_with($f, '_section_name')) {
            return 'Additional section title';
        }
        if (str_ends_with($f, '_content')) {
            return 'Additional section content';
        }
        return 'Additional home page section text';
    }

    private function additionalSectionRule(string $field): string
    {
        $f = strtolower($field);
        if (str_ends_with($f, '_section_name')) {
            return 'Short title (max 60 characters).';
        }
        if (str_ends_with($f, '_content')) {
            return 'Short supporting text (1-2 sentences).';
        }
        return $this->homeFieldRule($field);
    }
}
