<?php

// Laravel Boost (dev only; inactive when APP_ENV=production). Merged over the package config.
return [
    // Laravel Cloud is not used: the app runs portable on a Windows LAN PC (see .agents/).
    'guidelines' => ['exclude' => ['deployments']],

    'agents' => [
        // CLAUDE.md only imports AGENTS.md, so Boost writes its block there once for every agent.
        'claude_code' => ['guidelines_path' => 'AGENTS.md'],
    ],
];
