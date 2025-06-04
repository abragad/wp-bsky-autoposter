<?php

function process_inline_hashtags($message, $inline_hashtags = true) {
    if (empty($inline_hashtags)) {
        return $message;
    }

    // Extract hashtags from the end of the message
    $parts = explode(' ', $message);
    $hashtags = array();
    $main_text = array();
    $in_hashtags = false;

    // Split message into main text and hashtags
    foreach ($parts as $part) {
        if (strpos($part, '#') === 0) {
            $in_hashtags = true;
            $hashtags[] = $part;
        } else {
            if ($in_hashtags) {
                $in_hashtags = false;
                $main_text[] = $part;
            } else {
                $main_text[] = $part;
            }
        }
    }

    if (empty($hashtags)) {
        return $message;
    }

    $processed_hashtags = array();
    $main_text_str = implode(' ', $main_text);

    foreach ($hashtags as $hashtag) {
        if (preg_match('/[^a-zA-Z0-9_]/', substr($hashtag, 1))) {
            $processed_hashtags[] = $hashtag;
            continue;
        }

        $word = substr($hashtag, 1);
        // Updated regex: match only if not part of a larger word (no letter, digit, or underscore before/after)
        $pattern = '/(?<![a-zA-Z0-9_])' . preg_quote($word, '/') . '(?![a-zA-Z0-9_])/i';
        if (preg_match($pattern, $main_text_str, $matches)) {
            $replacement = '#' . $matches[0];
            $main_text_str = preg_replace($pattern, $replacement, $main_text_str, 1);
        } else {
            $processed_hashtags[] = $hashtag;
        }
    }

    $result = trim($main_text_str);
    if (!empty($processed_hashtags)) {
        $result .= ' ' . implode(' ', $processed_hashtags);
    }

    return $result;
}

// Test cases
$tests = [
    [
        'input' => 'XChat sarà una piattaforma di messaggistica #x',
        'expected' => 'XChat sarà una piattaforma di messaggistica #x',
        'desc' => 'Should NOT link X in XChat'
    ],
    [
        'input' => 'La piattaforma X sarà lanciata #x',
        'expected' => 'La piattaforma #X sarà lanciata',
        'desc' => 'Should link standalone X'
    ],
    [
        'input' => 'WhatsApp è popolare #whatsapp',
        'expected' => '#WhatsApp è popolare',
        'desc' => 'Should link WhatsApp'
    ],
    [
        'input' => 'Parliamo di WhatsApp e Telegram #whatsapp #telegram',
        'expected' => 'Parliamo di #WhatsApp e #Telegram',
        'desc' => 'Should link both hashtags'
    ],
    [
        'input' => 'XChat e WhatsApp sono app #x #whatsapp',
        'expected' => 'XChat e #WhatsApp sono app #x',
        'desc' => 'Should only link WhatsApp, not X in XChat'
    ],
];

foreach ($tests as $test) {
    $output = process_inline_hashtags($test['input']);
    $pass = $output === $test['expected'] ? 'PASS' : 'FAIL';
    echo "{$test['desc']}:\n";
    echo "Input:    {$test['input']}\n";
    echo "Expected: {$test['expected']}\n";
    echo "Output:   $output\n";
    echo "Result:   $pass\n\n";
} 