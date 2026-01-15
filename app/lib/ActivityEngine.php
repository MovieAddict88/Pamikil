<?php
declare(strict_types=1);

final class ActivityEngine
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public static function starsFromScore(int $scorePercent): int
    {
        if ($scorePercent >= 95) {
            return 5;
        }
        if ($scorePercent >= 85) {
            return 4;
        }
        if ($scorePercent >= 70) {
            return 3;
        }
        if ($scorePercent >= 55) {
            return 2;
        }
        return 1;
    }

    public static function coinsFromStars(int $stars): int
    {
        return $stars * 5;
    }

    /** @param array<string,mixed> $details */
    public function completeActivity(int $userId, int $activityId, int $scorePercent, ?int $stars = null, ?int $coinsEarned = null, array $details = []): void
    {
        $stars = $stars ?? self::starsFromScore($scorePercent);
        $coinsEarned = $coinsEarned ?? self::coinsFromStars($stars);

        $this->db->beginTransaction();
        try {
            $existing = $this->db->fetch(
                'SELECT id, attempts FROM activity_progress WHERE user_id = :u AND activity_id = :a LIMIT 1',
                ['u' => $userId, 'a' => $activityId]
            );

            if ($existing) {
                $attempts = (int)($existing['attempts'] ?? 0) + 1;
                $this->db->execute(
                    'UPDATE activity_progress
                     SET status = "completed", score_percent = :score, stars = :stars, coins_earned = coins_earned + :coins,
                         attempts = :attempts, completed_at = NOW(), updated_at = NOW(), details_json = :details
                     WHERE id = :id',
                    [
                        'score' => $scorePercent,
                        'stars' => $stars,
                        'coins' => $coinsEarned,
                        'attempts' => $attempts,
                        'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                        'id' => (int)$existing['id'],
                    ]
                );
            } else {
                $this->db->execute(
                    'INSERT INTO activity_progress (user_id, activity_id, status, score_percent, stars, coins_earned, attempts, started_at, completed_at, updated_at, details_json)
                     VALUES (:u, :a, "completed", :score, :stars, :coins, 1, NOW(), NOW(), NOW(), :details)',
                    [
                        'u' => $userId,
                        'a' => $activityId,
                        'score' => $scorePercent,
                        'stars' => $stars,
                        'coins' => $coinsEarned,
                        'details' => json_encode($details, JSON_UNESCAPED_UNICODE),
                    ]
                );
            }

            $this->db->execute(
                'INSERT INTO coins_ledger (user_id, delta, reason, ref_type, ref_id, created_at)
                 VALUES (:u, :d, :r, :t, :id, NOW())',
                [
                    'u' => $userId,
                    'd' => $coinsEarned,
                    'r' => 'Completed activity',
                    't' => 'activity',
                    'id' => $activityId,
                ]
            );

            $this->db->execute('UPDATE users SET coins = coins + :d WHERE id = :u', ['d' => $coinsEarned, 'u' => $userId]);

            $this->unlockNextActivities($userId, $activityId);
            $this->evaluateBadges($userId, $activityId, $scorePercent);

            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function unlockNextActivities(int $userId, int $completedActivityId): void
    {
        $next = $this->db->fetchAll(
            'SELECT ap.activity_id
             FROM activity_prerequisites ap
             WHERE ap.prereq_activity_id = :done',
            ['done' => $completedActivityId]
        );

        foreach ($next as $row) {
            $this->db->execute(
                'INSERT IGNORE INTO user_activity_unlocks (user_id, activity_id, unlocked_at)
                 VALUES (:u, :a, NOW())',
                ['u' => $userId, 'a' => (int)$row['activity_id']]
            );
        }
    }

    private function evaluateBadges(int $userId, int $activityId, int $scorePercent): void
    {
        $activity = $this->db->fetch('SELECT type FROM activities WHERE id = :id', ['id' => $activityId]);
        $type = $activity ? (string)$activity['type'] : '';

        $stats = $this->db->fetch(
            'SELECT
                COUNT(*) AS total_completed,
                SUM(CASE WHEN score_percent >= 95 THEN 1 ELSE 0 END) AS near_perfect
             FROM activity_progress
             WHERE user_id = :u AND status = "completed"',
            ['u' => $userId]
        ) ?? ['total_completed' => 0, 'near_perfect' => 0];

        $totalCompleted = (int)$stats['total_completed'];
        $nearPerfect = (int)$stats['near_perfect'];

        $coins = $this->db->fetch('SELECT coins FROM users WHERE id = :u', ['u' => $userId]);
        $coinBalance = (int)($coins['coins'] ?? 0);

        $this->maybeAwardBadge($userId, 'FIRST_STEPS', $totalCompleted >= 1);
        $this->maybeAwardBadge($userId, 'TEN_ACTIVITIES', $totalCompleted >= 10);
        $this->maybeAwardBadge($userId, 'FIFTY_ACTIVITIES', $totalCompleted >= 50);

        $this->maybeAwardBadge($userId, 'PERFECT_SCORE', $scorePercent >= 100);
        $this->maybeAwardBadge($userId, 'NEAR_PERFECT_5', $nearPerfect >= 5);

        $this->maybeAwardBadge($userId, 'COIN_COLLECTOR_100', $coinBalance >= 100);
        $this->maybeAwardBadge($userId, 'COIN_COLLECTOR_500', $coinBalance >= 500);

        if ($type !== '') {
            $typeCount = $this->db->fetch(
                'SELECT COUNT(*) AS c
                 FROM activity_progress p
                 JOIN activities a ON a.id = p.activity_id
                 WHERE p.user_id = :u AND p.status = "completed" AND a.type = :t',
                ['u' => $userId, 't' => $type]
            );
            $c = (int)($typeCount['c'] ?? 0);

            $this->maybeAwardBadge($userId, 'QUIZ_WIZARD', $type === 'quiz' && $c >= 5);
            $this->maybeAwardBadge($userId, 'STORY_EXPLORER', $type === 'story' && $c >= 3);
            $this->maybeAwardBadge($userId, 'PUZZLE_BUILDER', $type === 'jigsaw' && $c >= 3);
            $this->maybeAwardBadge($userId, 'WORD_MATCHER', $type === 'image_word' && $c >= 5);
        }

        $streak = $this->db->fetch(
            'SELECT COUNT(DISTINCT DATE(completed_at)) AS days
             FROM activity_progress
             WHERE user_id = :u AND status = "completed" AND completed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
            ['u' => $userId]
        );
        $days = (int)($streak['days'] ?? 0);
        $this->maybeAwardBadge($userId, 'STREAK_7_DAYS', $days >= 7);

        $hour = (int)date('G');
        $this->maybeAwardBadge($userId, 'EARLY_BIRD', $hour >= 5 && $hour < 9);
        $this->maybeAwardBadge($userId, 'NIGHT_OWL', $hour >= 21);

        $types = $this->db->fetch(
            'SELECT COUNT(DISTINCT a.type) AS c
             FROM activity_progress p
             JOIN activities a ON a.id = p.activity_id
             WHERE p.user_id = :u AND p.status = "completed"',
            ['u' => $userId]
        );
        $distinctTypes = (int)($types['c'] ?? 0);
        $this->maybeAwardBadge($userId, 'ALL_ROUNDER', $distinctTypes >= 7);
    }

    private function maybeAwardBadge(int $userId, string $badgeCode, bool $eligible): void
    {
        if (!$eligible) {
            return;
        }

        $badge = $this->db->fetch('SELECT id, coin_reward FROM badges WHERE code = :c LIMIT 1', ['c' => $badgeCode]);
        if (!$badge) {
            return;
        }

        $existing = $this->db->fetch(
            'SELECT 1 FROM user_badges WHERE user_id = :u AND badge_id = :b LIMIT 1',
            ['u' => $userId, 'b' => (int)$badge['id']]
        );
        if ($existing) {
            return;
        }

        $this->db->execute(
            'INSERT INTO user_badges (user_id, badge_id, earned_at)
             VALUES (:u, :b, NOW())',
            ['u' => $userId, 'b' => (int)$badge['id']]
        );

        $reward = (int)($badge['coin_reward'] ?? 0);
        if ($reward > 0) {
            $this->db->execute(
                'INSERT INTO coins_ledger (user_id, delta, reason, ref_type, ref_id, created_at)
                 VALUES (:u, :d, :r, "badge", :bid, NOW())',
                ['u' => $userId, 'd' => $reward, 'r' => 'Earned badge: ' . $badgeCode, 'bid' => (int)$badge['id']]
            );
            $this->db->execute('UPDATE users SET coins = coins + :d WHERE id = :u', ['d' => $reward, 'u' => $userId]);
        }
    }
}
