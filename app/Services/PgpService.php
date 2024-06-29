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
        // $cryptGen->setAlgorithm(CRYPT_GPG_KEYGEN_ALGO_RSA); // Ensure we are using RSA
        // $cryptGen->setKeyLength($key_length); // Set key length

        // Generate the key pair
        $cryptKey = $cryptGen->setPassphrase($highEntropyToken)
            ->setKeyParams(Crypt_GPG_SubKey::ALGORITHM_RSA, $key_length,1)
            ->setSubKeyParams(Crypt_GPG_SubKey::ALGORITHM_RSA, $key_length,1)
            ->generateKey($userID);

        // Initialize the Crypt_GPG instance
        $crypt_gpg = new Crypt_GPG();
        $crypt_gpg->clearPassphrases();

        // Add the keys
        $privateKey = $crypt_gpg->addEncryptKey($cryptKey);
        $publicKey = $crypt_gpg->addDecryptKey($cryptKey);

        // Grab key id of private key
        $keyId = $crypt_gpg->getFingerprint($userID);
        $crypt_gpg->addPassphrase($keyId, $highEntropyToken);

        // Export the public and private keys
        $exportedPublicKey = $crypt_gpg->exportPublicKey($userID, true);
        $exportedPrivateKey = $crypt_gpg->exportPrivateKey($userID, true);

        return [
            'public_key' => $exportedPublicKey,
            'private_key' => $exportedPrivateKey,
        ];

    }


    public function encrypt($public_key, $message)
    {
        $recipientPublicKey = OpenPGP_Message::parse(OpenPGP::unarmor($public_key, 'PGP PUBLIC KEY BLOCK'));
        $data = new OpenPGP_LiteralDataPacket($message, ['format' => 'u']);
        $compressed = new OpenPGP_CompressedDataPacket($data);
        $encrypted = OpenPGP_Crypt_Symmetric::encrypt($recipientPublicKey, new OpenPGP_Message([$compressed]));

        return OpenPGP::enarmor($encrypted->to_bytes(), 'PGP MESSAGE');
    }

    public function decrypt($private_key, $encrypted_message, $passphrase)
    {
        try {
            $encryptedPrivateKey = OpenPGP_Message::parse(OpenPGP::unarmor($private_key, 'PGP PRIVATE KEY BLOCK'));
            // Try each secret key packet
            foreach ($encryptedPrivateKey as $p) {
                if (! ($p instanceof OpenPGP_SecretKeyPacket)) {
                    continue;
                }
                $keyd = OpenPGP_Crypt_Symmetric::decryptSecretKey($passphrase, $p);
                $msg = OpenPGP_Message::parse(OpenPGP::unarmor($encrypted_message, 'PGP MESSAGE'));
                $decryptor = new OpenPGP_Crypt_RSA($keyd);
                $decrypted = $decryptor->decrypt($msg);
                $data_packet = $decrypted->packets[0]->data->packets[0];

                return $data_packet->data;
            }
        } catch (\Exception $e) {
            return response()->json(['error' => 'Unfortunately we were unable to decrypt your message at this time. Please verify you are using the correct password and try again.'], 403);
        }
    }
}
