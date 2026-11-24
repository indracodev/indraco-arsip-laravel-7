<?php

// Laravel Boost (dev only; inactive when APP_ENV=production). Merged over the package config.
return [
    'guidelines' => [
        // Laravel Cloud is not used: the app runs portable on a Windows LAN PC (see .agents/).
        // No Tailwind and no Vite/Node (owner decision: pure CSS + local Alpine.js). The Vite
        // note inside "foundation" is removed by the override .ai/guidelines/foundation.blade.php.
        'exclude' => ['deployments', 'tailwindcss/core', 'tailwindcss/v3', 'tailwindcss/v4'],
    ],

    'skills' => [
        'exclude' => ['tailwindcss-development'],
    ],

    'agents' => [
        // CLAUDE.md only imports AGENTS.md, so Boost writes its block there once for every agent.
        'claude_code' => ['guidelines_path' => 'AGENTS.md'],
    ],
];
