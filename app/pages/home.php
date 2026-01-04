<?php
declare(strict_types=1);
$__pageTitle = 'Home · ' . (string)config_get('app.name', 'Pamikil Learning');

$user = current_user();
?>
<section class="hero">
    <div class="hero__content">
        <h1 class="hero__title">A safe, fun learning space for kids</h1>
        <p class="hero__subtitle">Quizzes, stories, puzzles, flashcards, and more — with rewards, badges, and progress tracking.</p>

        <div class="hero__actions">
            <a class="btn btn--primary" href="<?= e(url('/activities')) ?>">Explore activities</a>

            <?php if (!$user): ?>
                <a class="btn" href="<?= e(url('/register')) ?>">Create an account</a>
            <?php else: ?>
                <?php if (($user['role'] ?? '') === 'student'): ?>
                    <a class="btn" href="<?= e(url('/student')) ?>">Go to my dashboard</a>
                <?php elseif (($user['role'] ?? '') === 'parent'): ?>
                    <a class="btn" href="<?= e(url('/parent')) ?>">Go to parent dashboard</a>
                <?php else: ?>
                    <a class="btn" href="<?= e(url('/admin')) ?>">Go to admin</a>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <div class="cards grid">
            <article class="card">
                <h2 class="card__title">7+ activity types</h2>
                <p class="card__text">Quiz, story, drag &amp; drop, flashcards, jigsaw, crossword, and image-to-word matching.</p>
            </article>
            <article class="card">
                <h2 class="card__title">Gamified learning</h2>
                <p class="card__text">Earn coins, unlock items, collect badges, and track weekly leaderboards.</p>
            </article>
            <article class="card">
                <h2 class="card__title">Built for families</h2>
                <p class="card__text">Parent monitoring, time limits, and content restrictions — plus admin content management.</p>
            </article>
        </div>
    </div>
</section>
