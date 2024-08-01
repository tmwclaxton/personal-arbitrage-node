<?php

namespace  App\Services;

use App\Http\Controllers\Controller;
use Crypt_GPG;
use Crypt_GPG_KeyGenerator;
use Crypt_GPG_SubKey;

class PgpService extends Controller
{
    // Utility function to generate SHA-256 hash
    function sha256($data) {
        return hash('sha256', $data, true);
    }

    public function generate_keypair($highEntropyToken, $key_length = 2048)
    {
        // Generate the SHA-256 hash of the SHA-256 hash of the high-entropy token
        $hashedToken = bin2hex($this->sha256(bin2hex($this->sha256($highEntropyToken))));
        $userID = "RoboSats ID: " . $hashedToken;

        // Initialize the key generator
        $cryptGen = new Crypt_GPG_KeyGenerator();

        $newTime = strtotime('-24 hours', time());

        // Generate the key pair
        $cryptKey = $cryptGen->setPassphrase($highEntropyToken)
            //  set creation date  --faked-system-time epoch
            ->setEngineOptions(array('gen-key' =>  '--faked-system-time ' . $newTime))
            ->setKeyParams(Crypt_GPG_SubKey::ALGORITHM_RSA, $key_length,1)
            ->setSubKeyParams(Crypt_GPG_SubKey::ALGORITHM_RSA, $key_length,2)
            ->generateKey($userID);

        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // Add the keys
        $privateKey = $crypt_gpg->addEncryptKey($cryptKey);
        $publicKey = $crypt_gpg->addDecryptKey($cryptKey);
        $signKey = $crypt_gpg->addSignKey($cryptKey, $highEntropyToken);

        // Grab key id of private key
        $keyId = $crypt_gpg->getFingerprint($userID);
        $crypt_gpg->addPassphrase($keyId, $highEntropyToken);

        // // sign some data
        // $signKeyId = $crypt_gpg->getFingerprint($userID);
        // $signed = $crypt_gpg->sign('hello world', Crypt_GPG::SIGN_MODE_CLEAR);
        // dd($signed);

        // Export the public and private keys
        $exportedPublicKey = $crypt_gpg->exportPublicKey($userID, true);
        $exportedPrivateKey = $crypt_gpg->exportPrivateKey($userID, true);
        // $exportedSignKey = $crypt_gpg->exportSignKey($userID, true);

        return [
            'public_key' => $exportedPublicKey,
            'private_key' => $exportedPrivateKey,

        ];

    }


    public function encrypt($publicKey, $message)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the private key
        $publicKeyImport = $crypt_gpg->importKey($publicKey);
        $fingerPrint = $publicKeyImport['fingerprint'];

        // Add the keys
        $private_key = $crypt_gpg->addEncryptKey($fingerPrint);

        // Encrypt the message
        $encrypted = $crypt_gpg->encrypt($message, true);

        return $encrypted;

    }

    // encrypt and sign
    public function encryptAndSign($publicKey, $privateKey, $message, $passphrase, $peerPublicKey = null)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the public key
        $publicKeyImport = $crypt_gpg->importKey($publicKey);
        $fingerPrintPublic = $publicKeyImport['fingerprint'];
        $privateKeyImport = $crypt_gpg->importKey($privateKey);
        $fingerPrintPrivate = $privateKeyImport['fingerprint'];

        // Add the keys
        $crypt_gpg->addSignKey($fingerPrintPrivate, $passphrase);
        $crypt_gpg->addPassphrase($fingerPrintPrivate, $passphrase);

        $crypt_gpg->addEncryptKey($fingerPrintPublic);

        if ($peerPublicKey) {
            $peerPublicKeyImport = $crypt_gpg->importKey($peerPublicKey);
            $peerFingerPrint = $peerPublicKeyImport['fingerprint'];
            $crypt_gpg->addEncryptKey($peerFingerPrint);
        }


        $newTime = strtotime('-24 hours', time());

        // Encrypt the message
        $encrypted = $crypt_gpg->setEngineOptions(array(
            'sign' =>  '--faked-system-time ' . $newTime
        ))->encryptAndSign($message, true);
        // dd($encrypted);


        return $encrypted;

    }

    public function decrypt($private_key, $encrypted_message, $passphrase)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the public key
        $privateKeyImport = $crypt_gpg->importKey($private_key);
        $fingerPrint = $privateKeyImport['fingerprint'];

        // Add the keys
        $private_key = $crypt_gpg->addDecryptKey($fingerPrint, $passphrase);
        // $crypt_gpg->addPassphrase($fingerPrint, $passphrase);

        // Decrypt the message
        $decrypted = $crypt_gpg->decrypt($encrypted_message);

        return $decrypted;

    }

    public function sign($privateKey, $message, $passphrase = null, $publicKey = null)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the public key
        $privateKeyImport = $crypt_gpg->importKey($privateKey);
        $fingerPrintPrivate = $privateKeyImport['fingerprint'];

        // Add the keys
        $crypt_gpg->addSignKey($fingerPrintPrivate, $passphrase);


        // Sign the message
        $newTime = strtotime('-24 hours', time());

        // Encrypt the message
        $signed = $crypt_gpg->setEngineOptions(array(
            'sign' =>  '--faked-system-time ' . $newTime
        ))->sign($message, Crypt_GPG::SIGN_MODE_CLEAR);

        // if public key is provided verify the signature to check if it is valid
        if ($publicKey) {
            $publicKeyImport = $crypt_gpg->importKey($publicKey);
            $fingerPrintPublic = $publicKeyImport['fingerprint'];
            $crypt_gpg->addDecryptKey($fingerPrintPublic);
            try {
                $verified = $crypt_gpg->verify($signed);
                if ($verified) {
                    return $signed;
                } else {
                    return 'Signature verification failed';
                }
            } catch (\Exception $e) {
                return 'Signature verification failed';
            }
        }

        return $signed;
    }

    public function verify($public_key, $signed_message)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the public key
        $publicKeyImport = $crypt_gpg->importKey($public_key);
        $fingerPrint = $publicKeyImport['fingerprint'];

        // Add the keys
        $public_key = $crypt_gpg->addDecryptKey($fingerPrint);

        // Verify the message
        $verified = $crypt_gpg->verify($signed_message);

        return $verified;

    }

    public function generateX509Certificates(): array
    {
        // Generate the private key
        $privateKey = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ]);

        // Generate the public key
        $publicKey = openssl_pkey_get_details($privateKey);
        $publicKey = $publicKey["key"];

        // Generate the X509 certificate
        $x509 = openssl_csr_new([
            "countryName" => "UK",
            "stateOrProvinceName" => "Belfast",
            "localityName" => "Northern Ireland",
            "organizationName" => "Lightning Arbitrage Solutions",
            "organizationalUnitName" => "",
            "commonName" => "Lightning Arbitrage Solutions",
        ], $privateKey);

        // Sign the X509 certificate
        $x509 = openssl_csr_sign($x509, null, $privateKey, 365);

        // Export the private key
        openssl_pkey_export($privateKey, $privateKey);

        // Export the X509 certificate
        openssl_x509_export($x509, $x509);

        return [
            "private_key" => $privateKey,
            "public_key" => $publicKey,
            "x509" => $x509,
        ];

    }
}
