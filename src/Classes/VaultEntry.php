<?php
declare(strict_types=1);

class VaultEntry
{
    private PDO               $pdo;
    private EncryptionService $crypto;

    public function __construct()
    {
        $this->pdo    = Database::getInstance()->getPdo();
        $this->crypto = new EncryptionService();
    }

    public function create(
        int    $userId,
        string $masterKey,
        string $serviceName,
        string $password,
        string $notes = ''
    ): int {
        $encryptedPassword = $this->crypto->encrypt($password, $masterKey);
        $encryptedNotes    = $notes !== '' ? $this->crypto->encrypt($notes, $masterKey) : null;

        $stmt = $this->pdo->prepare(
            'INSERT INTO vault_entries (user_id, service_name, encrypted_password, notes)
             VALUES (:uid, :svc, :pwd, :notes)'
        );
        $stmt->execute([
            ':uid'   => $userId,
            ':svc'   => $serviceName,
            ':pwd'   => $encryptedPassword,
            ':notes' => $encryptedNotes,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function getAllForUser(int $userId, string $masterKey): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, service_name, encrypted_password, notes, created_at, updated_at
             FROM vault_entries WHERE user_id = :uid ORDER BY service_name'
        );
        $stmt->execute([':uid' => $userId]);

        $entries = [];
        foreach ($stmt->fetchAll() as $row) {
            $entries[] = $this->decryptRow($row, $masterKey);
        }
        return $entries;
    }

    public function getOne(int $entryId, int $userId, string $masterKey): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, service_name, encrypted_password, notes, created_at, updated_at
             FROM vault_entries WHERE id = :id AND user_id = :uid LIMIT 1'
        );
        $stmt->execute([':id' => $entryId, ':uid' => $userId]);
        $row = $stmt->fetch();

        if (!$row) {
            throw new RuntimeException('Entry not found.');
        }

        return $this->decryptRow($row, $masterKey);
    }

    public function update(
        int    $entryId,
        int    $userId,
        string $masterKey,
        string $serviceName,
        string $password,
        string $notes = ''
    ): void {
        $this->getOne($entryId, $userId, $masterKey);

        $encryptedPassword = $this->crypto->encrypt($password, $masterKey);
        $encryptedNotes    = $notes !== '' ? $this->crypto->encrypt($notes, $masterKey) : null;

        $stmt = $this->pdo->prepare(
            'UPDATE vault_entries
             SET service_name = :svc, encrypted_password = :pwd, notes = :notes
             WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([
            ':svc'   => $serviceName,
            ':pwd'   => $encryptedPassword,
            ':notes' => $encryptedNotes,
            ':id'    => $entryId,
            ':uid'   => $userId,
        ]);
    }

    public function delete(int $entryId, int $userId): void
    {
        $stmt = $this->pdo->prepare(
            'DELETE FROM vault_entries WHERE id = :id AND user_id = :uid'
        );
        $stmt->execute([':id' => $entryId, ':uid' => $userId]);

        if ($stmt->rowCount() === 0) {
            throw new RuntimeException('Entry not found or access denied.');
        }
    }

    private function decryptRow(array $row, string $masterKey): array
    {
        return [
            'id'           => (int) $row['id'],
            'service_name' => $row['service_name'],
            'password'     => $this->crypto->decrypt($row['encrypted_password'], $masterKey),
            'notes'        => $row['notes'] !== null
                                ? $this->crypto->decrypt($row['notes'], $masterKey)
                                : '',
            'created_at'   => $row['created_at'],
            'updated_at'   => $row['updated_at'],
        ];
    }
}
