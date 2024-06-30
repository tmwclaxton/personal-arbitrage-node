<?php

namespace  App\Services;

use App\Http\Controllers\Controller;
use Crypt_GPG;
use Crypt_GPG_KeyGenerator;
use Crypt_GPG_SubKey;
use Illuminate\Support\Facades\Crypt;
use OpenPGP;
use OpenPGP_CompressedDataPacket;
use OpenPGP_Crypt_RSA;
use OpenPGP_Crypt_Symmetric;
use OpenPGP_LiteralDataPacket;
use OpenPGP_Message;
use OpenPGP_PublicKeyPacket;
use OpenPGP_SecretKeyPacket;
use OpenPGP_SignaturePacket;
use OpenPGP_SignaturePacket_IssuerPacket;
use OpenPGP_SignaturePacket_KeyFlagsPacket;
use OpenPGP_UserIDPacket;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\RSA\Formats\Keys\PKCS1;

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


        // Generate the key pair
        $cryptKey = $cryptGen->setPassphrase($highEntropyToken)
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
        // $signed = $crypt_gpg->encryptAndSign('hello world', Crypt_GPG::SIGN_MODE_CLEAR);
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


    public function encrypt($privateKey, $message)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the private key
        $privateKeyImport = $crypt_gpg->importKey($privateKey);
        $fingerPrint = $privateKeyImport['fingerprint'];

        // Add the keys
        $private_key = $crypt_gpg->addEncryptKey($fingerPrint);

        // Encrypt the message
        $encrypted = $crypt_gpg->encrypt($message, true);

        return $encrypted;

    }

    public function decrypt($public_key, $encrypted_message, $passphrase)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the public key
        $publicKeyImport = $crypt_gpg->importKey($public_key);
        $fingerPrint = $publicKeyImport['fingerprint'];

        // Add the keys
        $public_key = $crypt_gpg->addDecryptKey($fingerPrint, $passphrase);
        // $crypt_gpg->addPassphrase($fingerPrint, $passphrase);

        // Decrypt the message
        $decrypted = $crypt_gpg->decrypt($encrypted_message);

        return $decrypted;

    }

    public function sign($privateKey, $message)
    {
        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // import the private key
        $privateKeyImport = $crypt_gpg->importKey($privateKey);
        $fingerPrint = $privateKeyImport['fingerprint'];

        // Add the keys
        $private_key = $crypt_gpg->addSignKey($fingerPrint);

        // Sign the message
        $signed = $crypt_gpg->sign($message, true);

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
}
