<?php
declare(strict_types=1);
$__pageTitle = 'Not found · ' . (string)config_get('app.name', 'Pamikil Learning');
http_response_code(404);
?>
<div class="panel">
    <h1>Page not found</h1>
    <p>That page doesn’t exist. Try the <a href="<?= e(url('/activities')) ?>">activities</a> page.</p>
</div>
