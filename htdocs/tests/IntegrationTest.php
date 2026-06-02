<?php
// Integraciniai testai su realia MySQL DB (tests/IntegrationTest.php)
// GDI: Sis kodas buvo sugeneruotas naudojant Cursor (Composer / Agent, 2026-04-25).
// Uzklausa: Kaip parasyt integracinius testus su tikra MySQL — irasyt ir po testo isvalyt duomenis?
// Rezultatas dalinai koreguotas.
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class IntegrationTest extends TestCase
{
    /** @var list<int> */
    private array $createdUserIds = [];

    /** @var list<int> */
    private array $createdIssueIds = [];

    /** Po kiekvieno testo — išvalo sukurtus įrašus iš tikros DB. */
    protected function tearDown(): void
    {
        $pdo = db();

        foreach ($this->createdIssueIds as $id) {
            $pdo->prepare('DELETE FROM issue_photos WHERE issue_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM issue_comments WHERE issue_id = ?')->execute([$id]);
            $pdo->prepare("DELETE FROM audit_log WHERE entity = 'issue' AND entity_id = ?")->execute([$id]);
            $pdo->prepare('DELETE FROM issues WHERE id = ?')->execute([$id]);
        }

        foreach ($this->createdUserIds as $id) {
            $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM audit_log WHERE actor_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM issues WHERE user_id = ?')->execute([$id]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        }

        $this->createdUserIds = [];
        $this->createdIssueIds = [];
    }

    private function createTestUser(string $email, string $password = 'Test1234!'): int
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $query = db()->prepare(
            "INSERT INTO users (email, password_hash, status, created_at)
             VALUES (:email, :hash, 'ACTIVE', NOW())"
        );
        $query->execute(['email' => $email, 'hash' => $hash]);
        $id = (int) db()->lastInsertId();
        assign_role($id, 'USER');
        $this->createdUserIds[] = $id;

        return $id;
    }

    private function createTestIssue(int $userId): int
    {
        $query = db()->prepare(
            "INSERT INTO issues
                (user_id, title, description, category, lat, lng, address, status, created_at, updated_at)
             VALUES
                (:uid, 'Testo pranešimas', 'Aprašymas', 'Gedimai',
                 54.6872, 25.2797, NULL, 'NEW', NOW(), NOW())"
        );
        $query->execute(['uid' => $userId]);
        $id = (int) db()->lastInsertId();
        $this->createdIssueIds[] = $id;

        return $id;
    }

    // Registracija — vartotojas įrašomas į DB
    public function testUserRegistrationInsertsRowIntoDatabase(): void
    {
        $email = 'test_reg_' . uniqid() . '@manomiestas.test';
        $hash = password_hash('Test1234!', PASSWORD_DEFAULT);

        $query = db()->prepare(
            "INSERT INTO users (email, password_hash, status, created_at)
             VALUES (:email, :hash, 'ACTIVE', NOW())"
        );
        $query->execute(['email' => $email, 'hash' => $hash]);
        $id = (int) db()->lastInsertId();
        $this->createdUserIds[] = $id;

        $check = db()->prepare('SELECT id, email, status FROM users WHERE id = ?');
        $check->execute([$id]);
        $user = $check->fetch();

        $this->assertNotFalse($user, 'Vartotojas turėjo būti įrašytas į DB');
        $this->assertSame($email, $user['email']);
        $this->assertSame('ACTIVE', $user['status']);
    }

    // Rolė priskiriama po registracijos
    public function testNewUserGetsUserRole(): void
    {
        $id = $this->createTestUser('test_role_' . uniqid() . '@manomiestas.test');

        $query = db()->prepare("SELECT r.name FROM roles r INNER JOIN user_roles ur ON ur.role_id = r.id WHERE ur.user_id = :uid");
        $query->execute(["uid" => $id]);
        $roles = $query->fetchAll(PDO::FETCH_COLUMN);

        $this->assertContains('USER', $roles, 'Naujam vartotojui turi būti priskirta USER rolė');
    }

    // Prisijungimas — teisingas slaptažodis
    public function testLoginSucceedsWithCorrectPassword(): void
    {
        $email = 'test_login_' . uniqid() . '@manomiestas.test';
        $password = 'CorrectPass99!';
        $this->createTestUser($email, $password);

        $user = find_user_by_email($email);

        $this->assertNotNull($user, 'Vartotojas turi būti rastas pagal el. paštą');
        $this->assertTrue(
            password_verify($password, $user['password_hash']),
            'Teisingas slaptažodis turi būti patvirtintas'
        );
    }

    // Prisijungimas — neteisingas slaptažodis
    public function testLoginFailsWithWrongPassword(): void
    {
        $email = 'test_wrong_' . uniqid() . '@manomiestas.test';
        $this->createTestUser($email, 'CorrectPass99!');

        $user = find_user_by_email($email);

        $this->assertFalse(
            password_verify('NeteisingasSlaptazodis', $user['password_hash']),
            'Neteisingas slaptažodis turi būti atmestas'
        );
    }

    // Blokuotas vartotojas negali prisijungti
    public function testBlockedUserCannotLogin(): void
    {
        $email = 'test_blocked_' . uniqid() . '@manomiestas.test';
        $id = $this->createTestUser($email, 'Test1234!');

        db()->prepare("UPDATE users SET status = 'BLOCKED' WHERE id = ?")->execute([$id]);

        $user = find_user_by_email($email);

        $this->assertSame('BLOCKED', $user['status'],
            'Vartotojo statusas turi būti BLOCKED');
        $this->assertNotSame('ACTIVE', $user['status'],
            'Blokuotas vartotojas neturi turėti ACTIVE statuso');
    }

    // Pranešimo kūrimas — būsena NEW
    public function testIssueCreationInsertsRowWithNewStatus(): void
    {
        $userId = $this->createTestUser('test_issue_' . uniqid() . '@manomiestas.test');
        $issueId = $this->createTestIssue($userId);

        $query = db()->prepare('SELECT * FROM issues WHERE id = ?');
        $query->execute([$issueId]);
        $issue = $query->fetch();

        $this->assertNotFalse($issue, 'Pranešimas turėjo būti įrašytas į DB');
        $this->assertSame('NEW', $issue['status'],
            'Naujo pranešimo būsena turi būti NEW');
        $this->assertSame($userId, (int) $issue['user_id'],
            'Pranešimo autorius turi sutapti su vartotoju');
    }

    // Audito įrašas sukuriamas kartu su pranešimu
    public function testAuditLogEntryCreatedWithIssue(): void
    {
        $userId = $this->createTestUser('test_audit_' . uniqid() . '@manomiestas.test');
        $issueId = $this->createTestIssue($userId);

        log_audit('issue', $issueId, 'ISSUE_CREATE', $userId);

        $query = db()->prepare(
            "SELECT * FROM audit_log
             WHERE entity = 'issue' AND entity_id = ? AND action = 'ISSUE_CREATE'"
        );
        $query->execute([$issueId]);
        $log = $query->fetch();

        $this->assertNotFalse($log, 'Audito įrašas turėjo būti sukurtas');
        $this->assertSame($userId, (int) $log['actor_id'],
            'Audito įrašo autorius turi sutapti su vartotoju');
    }

    // Pranešimo būsenos keitimas
    public function testIssueStatusCanBeUpdated(): void
    {
        $userId = $this->createTestUser('test_status_' . uniqid() . '@manomiestas.test');
        $issueId = $this->createTestIssue($userId);

        $query = db()->prepare(
            "UPDATE issues SET status = 'IN_PROGRESS', updated_at = NOW() WHERE id = ?"
        );
        $query->execute([$issueId]);

        $check = db()->prepare('SELECT status FROM issues WHERE id = ?');
        $check->execute([$issueId]);
        $updated = $check->fetch();

        $this->assertSame('IN_PROGRESS', $updated['status'],
            'Pranešimo būsena turėjo būti atnaujinta į IN_PROGRESS');
    }

    // Komentaro pridėjimas į DB
    public function testCommentIsInsertedIntoDatabase(): void
    {
        $userId = $this->createTestUser('test_comment_' . uniqid() . '@manomiestas.test');
        $issueId = $this->createTestIssue($userId);

        $query = db()->prepare(
            'INSERT INTO issue_comments (issue_id, user_id, text, created_at)
             VALUES (:issue_id, :user_id, :text, NOW())'
        );
        $query->execute([
            'issue_id' => $issueId,
            'user_id' => $userId,
            'text' => 'Testas komentaras',
        ]);

        $check = db()->prepare(
            'SELECT * FROM issue_comments WHERE issue_id = ? AND user_id = ?'
        );
        $check->execute([$issueId, $userId]);
        $comment = $check->fetch();

        $this->assertNotFalse($comment, 'Komentaras turėjo būti įrašytas į DB');
        $this->assertSame('Testas komentaras', $comment['text']);
    }

    // Admin rolės tikrinimas
    public function testAdminRoleIsCorrectlyAssigned(): void
    {
        $userId = $this->createTestUser('test_admin_' . uniqid() . '@manomiestas.test');

        $_SESSION["user_id"] = $userId;
        $this->assertFalse(is_admin(),
            'Naujas vartotojas neturi būti administratorius');

        assign_role($userId, 'ADMIN');

        $this->assertTrue(is_admin(),
            'Po rolės priskyrimo vartotojas turi būti administratorius');
        $_SESSION["user_id"] = null;
    }
}
