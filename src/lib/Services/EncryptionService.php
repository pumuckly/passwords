<?php
/**
 * This file is part of the Passwords App
 * created by Marius David Wieschollek
 * and licensed under the AGPL.
 */

namespace OCA\Passwords\Services;

use OCA\Passwords\Db\Keychain;
use OCA\Passwords\Db\RevisionInterface;
use OCA\Passwords\Encryption\EncryptionInterface;
use OCA\Passwords\Encryption\SseV1Encryption;
use OCP\AppFramework\IAppContainer;

/**
 * Class EncryptionService
 *
 * @package OCA\Passwords\Services
 */
class EncryptionService {

    const DEFAULT_CSE_ENCRYPTION   = 'none';
    const DEFAULT_SSE_ENCRYPTION   = 'SSEv1r2';
    const DEFAULT_SHARE_ENCRYPTION = 'SSSEv1r1';
    const CSE_ENCRYPTION_NONE      = 'none';
    const CSE_ENCRYPTION_V1R1      = 'CSEv1r1';
    const SSE_ENCRYPTION_NONE      = 'none';
    const SSE_ENCRYPTION_V1        = 'SSEv1r1';
    const SSE_ENCRYPTION_V1R2      = 'SSEv1r2';
    const SHARE_ENCRYPTION_V1      = 'SSSEv1r1';

    protected $encryptionMapping
        = [
            self::SSE_ENCRYPTION_V1   => SseV1Encryption::class,
            self::SSE_ENCRYPTION_V1R2 => SseV1Encryption::class,
        ];
    /**
     * @var IAppContainer
     */
    private $container;

    /**
     * EncryptionService constructor.
     *
     * @param IAppContainer $container
     */
    public function __construct(IAppContainer $container) {
        $this->container = $container;
    }

    /**
     * @param string $type
     *
     * @return EncryptionInterface
     * @throws \Exception
     */
    public function getEncryptionByType(string $type): EncryptionInterface {

        if(!isset($this->encryptionMapping[ $type ])) {
            throw new \Exception("Encryption type {$type} does not exsist");
        }

        return $this->container->query($this->encryptionMapping[ $type ]);
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws \Exception
     */
    public function encrypt(RevisionInterface $object): RevisionInterface {
        if(!$object->_isDecrypted()) return $object;

        $object->_setDecrypted(false);
        if($object->getSseType() === self::SSE_ENCRYPTION_NONE) return $object;
        $encryption = $this->getEncryptionByType($object->getSseType());

        return $encryption->encryptObject($object);
    }

    /**
     * @param RevisionInterface $object
     *
     * @return RevisionInterface
     * @throws \Exception
     */
    public function decrypt(RevisionInterface $object): RevisionInterface {
        if($object->_isDecrypted()) return $object;

        $object->_setDecrypted(true);
        if($object->getSseType() === self::SSE_ENCRYPTION_NONE) return $object;
        $encryption = $this->getEncryptionByType($object->getSseType());

        return $encryption->decryptObject($object);
    }

    /**
     * @param Keychain $keychain
     *
     * @return Keychain
     * @throws \Exception
     */
    public function encryptKeychain(Keychain $keychain): Keychain {
        if(!$keychain->_isDecrypted()) return $keychain;

        $keychain->_setDecrypted(false);
        if($keychain->getScope() === $keychain::SCOPE_CLIENT) return $keychain;

        $encryption = $this->getEncryptionByType($keychain->getType());

        return $encryption->encryptKeychain($keychain);
    }

    /**
     * @param Keychain $keychain
     *
     * @return Keychain
     * @throws \Exception
     */
    public function decryptKeychain(Keychain $keychain): Keychain {
        if($keychain->_isDecrypted()) return $keychain;

        $keychain->_setDecrypted(true);
        if($keychain->getScope() === $keychain::SCOPE_CLIENT) return $keychain;

        $encryption = $this->getEncryptionByType($keychain->getType());

        return $encryption->decryptKeychain($keychain);
    }

    /**
     * @param string|null $cseType
     *
     * @return string
     */
    public function getDefaultEncryption(string $cseType = null): string {
        if($cseType === self::CSE_ENCRYPTION_NONE || $cseType = null) {
            return self::DEFAULT_SSE_ENCRYPTION;
        }

        return self::SSE_ENCRYPTION_NONE;
    }
}