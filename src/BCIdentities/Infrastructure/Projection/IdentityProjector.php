<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Projection;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\IdentityEmail;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\IdentityIban;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\IdentityRawName;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\IdentityProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityEmailAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityIbanAdded;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityNationalIdCodeChanged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityOrganisationRegCodeChanged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityPersonNameChanged;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Identity\IdentityRawNameAdded;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\ProjectorTrait;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('identity')]
final class IdentityProjector implements IdentityProjectionRepositoryInterface
{
    use SubscriberUtil;
    use ProjectorTrait;

    public function findByNationalIdCode(string $nationalIdCode): array
    {
        return $this->findBy('nationalIdCode', $nationalIdCode);
    }

    public function findByOrganisationRegCode(string $organisationRegCode): array
    {
        return $this->findBy('organisationRegCode', $organisationRegCode);
    }

    public function findByIban(string $iban): array
    {
        /** @var list<IdentityIban> $identityIbans */
        $identityIbans = $this->getEntityManager()->getRepository(IdentityIban::class)
            ->findBy(['iban' => $iban]);

        return array_map(
            static fn (IdentityIban $identityIban): Identity => $identityIban->getIdentity(),
            $identityIbans,
        );
    }

    public function findByEmail(string $email): array
    {
        /** @var list<IdentityEmail> $identityEmails */
        $identityEmails = $this->getEntityManager()->getRepository(IdentityEmail::class)
            ->findBy(['email' => $email]);

        return array_map(
            static fn (IdentityEmail $identityEmail): Identity => $identityEmail->getIdentity(),
            $identityEmails,
        );
    }

    /**
     * @return list<Identity>
     */
    private function findBy(string $field, string $value): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('i')
            ->from(Identity::class, 'i')
            ->andWhere(sprintf('i.%s = :value', $field))
            ->setParameter('value', $value);

        /** @var list<Identity> $identities */
        $identities = $qb->getQuery()->getResult();

        return $identities;
    }

    #[Subscribe(IdentityRawNameAdded::class)]
    public function onIdentityNameChanged(Message $message): void
    {
        $event = $this->getEvent($message, IdentityRawNameAdded::class);
        if ($event->rawName === null) {
            return;
        }
        $identity = $this->loadOrCreateIdentity($event->identityId->toString());
        $identity->addRawName($event->rawName->toString());

        $this->flush($message);
    }

    #[Subscribe(IdentityPersonNameChanged::class)]
    public function onIdentityPersonNameChanged(Message $message): void
    {
        $event = $this->getEvent($message, IdentityPersonNameChanged::class);
        $identity = $this->loadOrCreateIdentity($event->identityId->toString());
        $identity->setGivenName($event->personName?->givenName);
        $identity->setFamilyName($event->personName?->familyName);

        $this->flush($message);
    }

    #[Subscribe(IdentityEmailAdded::class)]
    public function onIdentityEmailAdded(Message $message): void
    {
        $event = $this->getEvent($message, IdentityEmailAdded::class);
        if ($event->email === null) {
            return;
        }
        $identity = $this->loadOrCreateIdentity($event->identityId->toString());
        $identity->addEmail($event->email->toString());

        $this->flush($message);
    }

    #[Subscribe(IdentityIbanAdded::class)]
    public function onIdentityIbanAdded(Message $message): void
    {
        $event = $this->getEvent($message, IdentityIbanAdded::class);
        if ($event->iban === null) {
            return;
        }
        $identity = $this->loadOrCreateIdentity($event->identityId->toString());
        $identity->addIban($event->iban->value);

        $this->flush($message);
    }

    #[Subscribe(IdentityNationalIdCodeChanged::class)]
    public function onIdentityNationalIdCodeChanged(Message $message): void
    {
        $event = $this->getEvent($message, IdentityNationalIdCodeChanged::class);
        $identity = $this->loadOrCreateIdentity($event->identityId->toString());
        $identity->setNationalIdCode($event->nationalIdCode?->value);

        $this->flush($message);
    }

    #[Subscribe(IdentityOrganisationRegCodeChanged::class)]
    public function onIdentityOrganisationRegCodeChanged(Message $message): void
    {
        $event = $this->getEvent($message, IdentityOrganisationRegCodeChanged::class);
        $identity = $this->loadOrCreateIdentity($event->identityId->toString());
        $identity->setOrganisationRegCode($event->organisationRegCode?->value);

        $this->flush($message);
    }

    private function loadOrCreateIdentity(string $identityId): Identity
    {
        /** @var Identity|null $identity */
        $identity = $this->getEntityManager()->getRepository(Identity::class)->find($identityId);

        if ($identity === null) {
            $identity = new Identity();
            $identity->setIdentityId($identityId);
            $this->persist($identity);
        }

        return $identity;
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM ' . Identity::class)->execute();
        $this->getEntityManager()->createQuery('DELETE FROM ' . IdentityEmail::class)->execute();
        $this->getEntityManager()->createQuery('DELETE FROM ' . IdentityIban::class)->execute();
        $this->getEntityManager()->createQuery('DELETE FROM ' . IdentityRawName::class)->execute();
    }
}
