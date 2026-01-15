<?php
declare(strict_types=1);

auth()->logout();
Security::startSession(app_config());
flash('success', 'You have been logged out.');
redirect('/');
