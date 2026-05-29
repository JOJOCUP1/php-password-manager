<?php
declare(strict_types=1);

class EncryptionService
{
    private const CIPHER     = 'AES-256-CBC';
    private const KEY_BYTES  = 32;
    private const IV_BYTES   = 16;
    private const PBKDF2_ALG = 'sha256';
    private const PBKDF2_IT  = 100_000;

    public function generateMasterKey(): string
    {
        return random_bytes(self::KEY_BYTES);
    }

    public function encryptMasterKey(string $masterKey, string $plainPassword): string
    {
        $salt = random_bytes(16);
        $iv   = random_bytes(self::IV_BYTES);
        $kek  = $this->deriveKey($plainPassword, $salt);

        $encrypted = openssl_encrypt(
            $masterKey,
            self::CIPHER,
            $kek,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new RuntimeException('Failed to encrypt master key.');
        }

        return base64_encode($salt . $iv . $encrypted);
    }

    public function decryptMasterKey(string $encryptedBlob, string $plainPassword): string
    {
        $data = base64_decode($encryptedBlob, true);
        if ($data === false || strlen($data) < 48) {
            throw new RuntimeException('Invalid encrypted master key blob.');
        }

        $salt      = substr($data, 0,  16);
        $iv        = substr($data, 16, 16);
        $encrypted = substr($data, 32);

        $kek = $this->deriveKey($plainPassword, $salt);

        $masterKey = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $kek,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($masterKey === false) {
            throw new RuntimeException('Failed to decrypt master key – wrong password?');
        }

        return $masterKey;
    }

    public function rekeyMasterKey(
        string $encryptedBlob,
        string $oldPassword,
        string $newPassword
    ): string {
        $masterKey = $this->decryptMasterKey($encryptedBlob, $oldPassword);
        return $this->encryptMasterKey($masterKey, $newPassword);
    }

    public function encrypt(string $plaintext, string $masterKey): string
    {
        $iv = random_bytes(self::IV_BYTES);
        $encrypted = openssl_encrypt(
            $plaintext,
            self::CIPHER,
            $masterKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new RuntimeException('Encryption failed.');
        }

        return base64_encode($iv . $encrypted);
    }

    public function decrypt(string $blob, string $masterKey): string
    {
        $data = base64_decode($blob, true);
        if ($data === false || strlen($data) < self::IV_BYTES + 1) {
            throw new RuntimeException('Invalid encrypted data blob.');
        }

        $iv        = substr($data, 0, self::IV_BYTES);
        $encrypted = substr($data, self::IV_BYTES);

        $plaintext = openssl_decrypt(
            $encrypted,
            self::CIPHER,
            $masterKey,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($plaintext === false) {
            throw new RuntimeException('Decryption failed.');
        }

        return $plaintext;
    }

    private function deriveKey(string $password, string $salt): string
    {
        return hash_pbkdf2(
            self::PBKDF2_ALG,
            $password,
            $salt,
            self::PBKDF2_IT,
            self::KEY_BYTES,
            true
        );
    }
}
