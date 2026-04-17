<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Projection;

use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\ClaimProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimInReview;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForEmail;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForIban;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForNationalIdCode;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForOrganisationRegCode;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForPersonName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForRawName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimResolved;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\ProjectorTrait;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\NationalIdCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\OrganisationRegCode;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;

#[Projector('claim')]
final class ClaimProjector implements ClaimProjectionRepositoryInterface
{
    use SubscriberUtil;
    use ProjectorTrait;

    public function find(ClaimId $claimId): ?Claim
    {
        /** @var Claim|null $claim */
        $claim = $this->getEntityManager()->getRepository(Claim::class)->find($claimId->toString());

        return $claim;
    }

    public function findInReview(): array
    {
        /** @var list<Claim> $claims */
        $claims = $this->getEntityManager()->getRepository(Claim::class)->findBy(
            ['inReview' => true],
            ['updatedAt' => 'ASC', 'createdAt' => 'ASC'],
        );

        return $claims;
    }

    public function countInReview(): int
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(c.claimId)')
            ->from(Claim::class, 'c')
            ->where('c.inReview = :inReview')
            ->setParameter('inReview', true);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    #[Subscribe(ClaimCreated::class)]
    public function onClaimCreated(Message $message): void
    {
        $event = $this->getEvent($message, ClaimCreated::class);

        if ($this->find($event->claimId) !== null) {
            return;
        }

        $claim = new Claim();
        $claim->setClaimId($event->claimId->toString());
        $claim->setPaymentId($event->source->isPaymentContext() ? $event->source->getId() : null);
        $claim->setDonationId($event->source->isDonationContext() ? $event->source->getId() : null);
        $claim->setInReview(false);
        $claim->setResolved(false);
        $claim->setReviewReason(null);
        $claim->setCreatedAt($event->occuredOn);
        $claim->setUpdatedAt($event->occuredOn);
        $this->persist($claim);
        $this->persistTrackingPayload($this->getTrackingId($message), claimId: $event->claimId->toString());

        $this->flush($message);
    }

    #[Subscribe(ClaimPresentedForPersonName::class)]
    public function onClaimPresentedForPersonName(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForPersonName::class);

        $this->updateClaimValue($message, $event->claimId, $event->occuredOn, $event->value);
    }

    #[Subscribe(ClaimPresentedForRawName::class)]
    public function onClaimPresentedForRawName(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForRawName::class);

        $this->updateClaimValue($message, $event->claimId, $event->occuredOn, $event->value);
    }

    #[Subscribe(ClaimPresentedForEmail::class)]
    public function onClaimPresentedForEmail(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForEmail::class);

        $this->updateClaimValue($message, $event->claimId, $event->occuredOn, $event->value);
    }

    #[Subscribe(ClaimPresentedForIban::class)]
    public function onClaimPresentedForIban(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForIban::class);

        $this->updateClaimValue($message, $event->claimId, $event->occuredOn, $event->value);
    }

    #[Subscribe(ClaimPresentedForNationalIdCode::class)]
    public function onClaimPresentedForNationalIdCode(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForNationalIdCode::class);

        $this->updateClaimValue($message, $event->claimId, $event->occuredOn, $event->value);
    }

    #[Subscribe(ClaimPresentedForOrganisationRegCode::class)]
    public function onClaimPresentedForOrganisationRegCode(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForOrganisationRegCode::class);

        $this->updateClaimValue($message, $event->claimId, $event->occuredOn, $event->value);
    }

    #[Subscribe(ClaimInReview::class)]
    public function onClaimInReview(Message $message): void
    {
        $event = $this->getEvent($message, ClaimInReview::class);
        $claim = $this->findOneOrThrow($event->claimId);
        $claim->setUpdatedAt($event->occuredOn);
        $claim->setInReview(true);
        $claim->setReviewReason($event->reason->value);

        $this->flush($message);
    }

    #[Subscribe(ClaimResolved::class)]
    public function onClaimResolved(Message $message): void
    {
        $event = $this->getEvent($message, ClaimResolved::class);
        $claim = $this->findOneOrThrow($event->claimId);
        $claim->setUpdatedAt($event->occuredOn);
        $claim->setInReview(false);
        $claim->setResolved(true);
        $claim->setReviewReason(null);
        $claim->setIdentityId($event->identityId->toString());

        $this->flush($message);
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM ' . Claim::class)->execute();
    }

    private function findOneOrThrow(ClaimId $claimId): Claim
    {
        $claim = $this->find($claimId);

        if ($claim === null) {
            throw new \RuntimeException(sprintf('%s not found for claimId (%s)', Claim::class, $claimId->toString()));
        }

        return $claim;
    }

    private function updateClaimValue(
        Message $message,
        ClaimId $claimId,
        \DateTimeImmutable $occuredOn,
        PersonName|RawName|Email|Iban|NationalIdCode|OrganisationRegCode|null $value,
    ): void {
        $claim = $this->findOneOrThrow($claimId);
        $claim->setUpdatedAt($occuredOn);

        if ($value instanceof PersonName) {
            $claim->setGivenName($value->givenName);
            $claim->setFamilyName($value->familyName);
        }
        if ($value instanceof RawName) {
            $claim->setRawName($value->toString());
        }
        if ($value instanceof Email) {
            $claim->setEmail($value->toString());
        }
        if ($value instanceof Iban) {
            $claim->setIban($value->value);
        }
        if ($value instanceof NationalIdCode) {
            $claim->setNationalIdCode($value->value);
        }
        if ($value instanceof OrganisationRegCode) {
            $claim->setOrganisationRegCode($value->value);
        }

        $this->flush($message);
    }
}
