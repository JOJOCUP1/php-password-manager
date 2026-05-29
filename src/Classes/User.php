<?php
declare(strict_types=1);

class User
{
    private PDO               $pdo;
    private EncryptionService $crypto;

    public function __construct()
    {
        $this->pdo    = Database::getInstance()->getPdo();
        $this->crypto = new EncryptionService();
    }

    public function register(string $username, string $email, string $plainPassword): int
    {
        $this->assertUniqueUsername($username);
        $this->assertUniqueEmail($email);

        $passwordHash         = password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $masterKey            = $this->crypto->generateMasterKey();
        $encryptedMasterKey   = $this->crypto->encryptMasterKey($masterKey, $plainPassword);

        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, encryption_key_encrypted)
             VALUES (:username, :email, :hash, :key_enc)'
        );
        $stmt->execute([
            ':username' => $username,
            ':email'    => $email,
            ':hash'     => $passwordHash,
            ':key_enc'  => $encryptedMasterKey,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function login(string $username, string $plainPassword): array
    {
        $row = $this->findByUsername($username);

        if ($row === null || !password_verify($plainPassword, $row['password_hash'])) {
            throw new RuntimeException('Invalid username or password.');
        }

        $masterKey = $this->crypto->decryptMasterKey(
            $row['encryption_key_encrypted'],
            $plainPassword
        );

        return [
            'id'         => (int) $row['id'],
            'username'   => $row['username'],
            'email'      => $row['email'],
            'master_key' => $masterKey,
        ];
    }

    public function changePassword(int $userId, string $oldPassword, string $newPassword): void
    {
        $row = $this->findById($userId);
        if ($row === null) {
            throw new RuntimeException('User not found.');
        }

        if (!password_verify($oldPassword, $row['password_hash'])) {
            throw new RuntimeException('Old password is incorrect.');
        }

        $newEncryptedKey = $this->crypto->rekeyMasterKey(
            $row['encryption_key_encrypted'],
            $oldPassword,
            $newPassword
        );

        $newHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->pdo->prepare(
            'UPDATE users SET password_hash = :hash, encryption_key_encrypted = :key_enc
             WHERE id = :id'
        );
        $stmt->execute([
            ':hash'    => $newHash,
            ':key_enc' => $newEncryptedKey,
            ':id'      => $userId,
        ]);
    }

    private function findByUsername(string $username): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function assertUniqueUsername(string $username): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
        $stmt->execute([':u' => $username]);
        if ($stmt->fetch()) {
            throw new RuntimeException('Username already taken.');
        }
    }

    private function assertUniqueEmail(string $email): void
    {
        $stmt = $this->pdo->prepare('SELECT id FROM users WHERE email = :e LIMIT 1');
        $stmt->execute([':e' => $email]);
        if ($stmt->fetch()) {
            throw new RuntimeException('E-mail already registered.');
        }
    }
}
