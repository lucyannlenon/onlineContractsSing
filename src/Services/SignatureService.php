<?php

namespace App\Services;

class SignatureService
{
    public function __construct(
        private readonly string $configDir
    )
    {

    }

    public function generateKeys(): void
    {
        $privateKeyResource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        openssl_pkey_export($privateKeyResource, $privateKey);
        $publicKey = openssl_pkey_get_details($privateKeyResource)['key'];

        file_put_contents($this->getPrivateKeyLocal(), $privateKey);
        file_put_contents($this->getPublicKeyLocal(), $publicKey);
    }

    /**
     * @throws \Exception
     */
    public function sing(array $clientInfo): string
    {
        $clientInfoJson = json_encode($clientInfo);

        $privateKey = $this->getPrivateKey();

        openssl_sign($clientInfoJson, $signature, $privateKey, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    /**
     * @return string
     */
    public function getPrivateKeyLocal(): string
    {
        return $this->configDir . '/private.key';
    }

    /**
     * @return string
     */
    public function getPublicKeyLocal(): string
    {
        return $this->configDir . '/public.key';
    }

    /**
     * @return string
     */
    public function getPrivateKey(): string
    {
        if (!file_exists($this->getPrivateKeyLocal())) {
            throw new \Exception("Please Generate a Private Key");
        }
        return file_get_contents($this->getPrivateKeyLocal());
    }
}