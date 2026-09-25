<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Infrastructure\Projection;

use Doctrine\ORM\Query\ResultSetMappingBuilder;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Claim;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\ClaimConnection;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\ClaimPresentation;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model\Identity;
use ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Port\ClaimProjectionRepositoryInterface;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCorrelated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCorrelationRemoved;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimCreated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimDataInvalidated;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimEvidenceLevel;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimId;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForEmail;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForIban;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForLegalIdentifier;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForPersonName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimPresentedForRawName;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSource;
use ErgoSarapu\DonationBundle\BCIdentities\Domain\Claim\ClaimSourceContext;
use ErgoSarapu\DonationBundle\SharedInfrastructure\Patchlevel\ProjectorTrait;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Email;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\Iban;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\LegalIdentifier;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\PersonName;
use ErgoSarapu\DonationBundle\SharedKernel\ValueObject\RawName;
use Override;
use Patchlevel\EventSourcing\Attribute\Projector;
use Patchlevel\EventSourcing\Attribute\Subscribe;
use Patchlevel\EventSourcing\Attribute\Teardown;
use Patchlevel\EventSourcing\Message\Message;
use Patchlevel\EventSourcing\Subscription\Subscriber\SubscriberUtil;
use RuntimeException;

#[Projector('claim')]
final class ClaimProjector implements ClaimProjectionRepositoryInterface
{
    use ProjectorTrait;
    use SubscriberUtil;

    #[Override]
    public function find(ClaimId $claimId): ?Claim
    {
        /** @var Claim|null $claim */
        $claim = $this->getEntityManager()->getRepository(Claim::class)
            ->find($claimId->toString());

        return $claim;
    }

    #[Override]
    public function findBySource(ClaimSourceContext $sourceContext, string $sourceId): ?Claim
    {
        /** @var Claim|null $claim */
        $claim = $this->getEntityManager()->getRepository(Claim::class)->findOneBy([
            'sourceContext' => $sourceContext->value,
            'sourceId' => $sourceId,
        ]);

        return $claim;
    }

    #[Subscribe(ClaimCreated::class)]
    public function onClaimCreated(Message $message): void
    {
        $event = $this->getEvent($message, ClaimCreated::class);
        $this->createClaim(
            $event->claimId,
            $event->source,
            $event->initialIdentityId->toString(),
        );

        $this->flush($message);
    }

    #[Subscribe(ClaimCorrelated::class)]
    public function onClaimCorrelated(Message $message): void
    {
        $event = $this->getEvent($message, ClaimCorrelated::class);
        $claim = $this->findOrThrow($event->claimId);
        $correlatedClaim = $this->findOrThrow($event->correlatedClaimId);
        $this->connectClaims($claim, $correlatedClaim, ClaimConnection::REASON_CORRELATED);

        $this->flush($message);
    }

    #[Subscribe(ClaimCorrelationRemoved::class)]
    public function onClaimCorrelationRemoved(Message $message): void
    {
        $event = $this->getEvent($message, ClaimCorrelationRemoved::class);
        $claim = $this->findOrThrow($event->claimId);
        $correlatedClaim = $this->findOrThrow($event->correlatedClaimId);

        $claim->removeConnection(
            $event->correlatedClaimId->toString(),
            ClaimConnection::REASON_CORRELATED,
        );
        $correlatedClaim->removeConnection(
            $claim->getClaimId(),
            ClaimConnection::REASON_CORRELATED,
        );
        $this->getEntityManager()->flush();
        $this->setConnectedClaimsIdentity($claim, $correlatedClaim);
        $this->flush($message);
    }

    #[Subscribe(ClaimPresentedForPersonName::class)]
    public function onClaimPresentedForPersonName(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForPersonName::class);
        $this->updateClaimValue($message, $event->claimId, $event->value, $event->evidenceLevel);
    }

    #[Subscribe(ClaimPresentedForRawName::class)]
    public function onClaimPresentedForRawName(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForRawName::class);
        $this->updateClaimValue($message, $event->claimId, $event->value, $event->evidenceLevel);
    }

    #[Subscribe(ClaimPresentedForEmail::class)]
    public function onClaimPresentedForEmail(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForEmail::class);
        $this->updateClaimValue($message, $event->claimId, $event->value, $event->evidenceLevel);
    }

    #[Subscribe(ClaimPresentedForIban::class)]
    public function onClaimPresentedForIban(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForIban::class);
        $this->updateClaimValue($message, $event->claimId, $event->value, $event->evidenceLevel);
    }

    #[Subscribe(ClaimPresentedForLegalIdentifier::class)]
    public function onClaimPresentedForLegalIdentifier(Message $message): void
    {
        $event = $this->getEvent($message, ClaimPresentedForLegalIdentifier::class);
        $this->updateClaimValue($message, $event->claimId, $event->value, $event->evidenceLevel);
    }

    #[Subscribe(ClaimDataInvalidated::class)]
    public function onClaimDataInvalidated(Message $message): void
    {
        $event = $this->getEvent($message, ClaimDataInvalidated::class);
        $claim = $this->findOrThrow($event->claimId);
        $claim->clearPresentations();

        $this->flush($message);
    }

    private function updateClaimValue(
        Message $message,
        ClaimId $claimId,
        PersonName|RawName|Email|Iban|LegalIdentifier|null $value,
        ClaimEvidenceLevel $evidenceLevel,
    ): void {
        $claim = $this->findOrThrow($claimId);
        $presentation = $claim->getPresentationForEvidenceLevel($evidenceLevel->value) ?? $claim->addPresentation($evidenceLevel->value);
        $this->setClaimValue($presentation, $value);

        $this->flush($message);
        $this->connectClaimToMatchingValue($claim, $value);
        $this->flush($message);
    }

    private function setClaimValue(ClaimPresentation $presentation, PersonName|RawName|Email|Iban|LegalIdentifier|null $value): void
    {
        if ($value instanceof PersonName) {
            $presentation->setGivenName($value->givenName);
            $presentation->setFamilyName($value->familyName);
        }
        if ($value instanceof RawName) {
            $presentation->setRawName($value->toString());
        }
        if ($value instanceof Email) {
            $presentation->setEmail($value->toString());
        }
        if ($value instanceof Iban) {
            $presentation->setIban($value->value);
        }
        if ($value instanceof LegalIdentifier) {
            $presentation->setLegalIdentifier($value->value);
        }
    }

    private function connectClaimToMatchingValue(
        Claim $claim,
        PersonName|RawName|Email|Iban|LegalIdentifier|null $value,
    ): void {
        if ($claim->getConnectedClaimIds() !== []) {
            return;
        }

        if ($value instanceof Email) {
            $this->connectClaimToMatchingPresentation(
                $claim,
                'email',
                $value->toString(),
                ClaimConnection::REASON_EMAIL,
            );

            return;
        }

        if ($value instanceof LegalIdentifier) {
            $this->connectClaimToMatchingPresentation(
                $claim,
                'legalIdentifier',
                $value->value,
                ClaimConnection::REASON_LEGAL_IDENTIFIER,
            );

            return;
        }

        if (!$value instanceof Iban) {
            return;
        }

        $this->connectClaimToMatchingPresentation(
            $claim,
            'iban',
            $value->value,
            ClaimConnection::REASON_IBAN,
        );
    }

    private function connectClaimToMatchingPresentation(
        Claim $claim,
        string $field,
        string $value,
        string $reason,
    ): void {
        /** @var Claim|null $matchingClaim */
        $matchingClaim = $this->getEntityManager()->getRepository(Claim::class)
            ->createQueryBuilder('claim')
            ->innerJoin('claim.presentations', 'presentation')
            ->where('claim.claimId != :claimId')
            ->andWhere(sprintf('presentation.%s = :value', $field))
            ->setParameter('claimId', $claim->getClaimId())
            ->setParameter('value', $value)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($matchingClaim !== null) {
            $this->connectClaims($claim, $matchingClaim, $reason);
        }
    }

    private function findOrThrow(ClaimId $claimId): Claim
    {
        return $this->find($claimId) ?? throw new RuntimeException(sprintf('Claim not found for claimId (%s)', $claimId->toString()));
    }

    private function createClaim(
        ClaimId $claimId,
        ClaimSource $source,
        string $initialIdentityId,
    ): Claim {
        $claim = new Claim();
        $claim->setClaimId($claimId->toString());
        $claim->setInitialIdentityId($initialIdentityId);
        $claim->setSourceContext($source->context->value);
        $claim->setSourceType($source->type);
        $claim->setSourceId($source->id);
        $claim->setIdentity($this->createIdentity($initialIdentityId));

        $this->persist($claim);

        return $claim;
    }

    private function connectClaims(Claim $claim, Claim $connectedClaim, string $reason): void
    {
        $claim->addConnection($connectedClaim->getClaimId(), $reason);
        $connectedClaim->addConnection($claim->getClaimId(), $reason);
        $this->getEntityManager()->flush();
        $this->setConnectedClaimsIdentity($claim);
    }

    /**
     * @return non-empty-list<Claim>
     */
    private function connectedClaims(Claim $claim): array
    {
        $resultSetMapping = new ResultSetMappingBuilder($this->getEntityManager());
        $resultSetMapping->addRootEntityFromClassMetadata(Claim::class, 'claim');
        $resultSetMapping->addJoinedEntityFromClassMetadata(Identity::class, 'identity', 'claim', 'identity');

        $sql = sprintf(
            <<<'SQL'
                WITH RECURSIVE connected_claim_ids (claim_id) AS (
                    SELECT :claimId
                    UNION
                    SELECT connection.connected_claim_id
                    FROM projection_claim_connection connection
                    INNER JOIN connected_claim_ids component
                        ON component.claim_id = connection.claim_id
                )
                SELECT %s
                FROM projection_claim claim
                INNER JOIN projection_identity identity
                    ON identity.identity_id = claim.identity_id
                INNER JOIN connected_claim_ids component
                    ON component.claim_id = claim.claim_id
                SQL,
            $resultSetMapping->generateSelectClause(),
        );

        /** @var list<Claim> $connectedClaims */
        $connectedClaims = $this->getEntityManager()->createNativeQuery(
            $sql,
            $resultSetMapping,
        )->setParameter('claimId', $claim->getClaimId())->getResult();

        if ($connectedClaims === []) {
            throw new RuntimeException(sprintf('Connected claim "%s" was not found.', $claim->getClaimId()));
        }

        return $connectedClaims;
    }

    private function setConnectedClaimsIdentity(Claim ...$claims): void
    {
        $assignedClaimIds = [];

        foreach ($claims as $claim) {
            if (isset($assignedClaimIds[$claim->getClaimId()])) {
                continue;
            }

            $connectedClaims = $this->connectedClaims($claim);
            $identity = $this->identityFor($connectedClaims);
            foreach ($connectedClaims as $connectedClaim) {
                $assignedClaimIds[$connectedClaim->getClaimId()] = true;
                $connectedClaim->setIdentity($identity);
            }
        }
    }

    /**
     * @param non-empty-list<Claim> $connectedClaims
     */
    private function identityFor(array $connectedClaims): Identity
    {
        $initialIdentityId = $connectedClaims[0]->getInitialIdentityId();
        foreach ($connectedClaims as $connectedClaim) {
            if ($connectedClaim->getInitialIdentityId() < $initialIdentityId) {
                $initialIdentityId = $connectedClaim->getInitialIdentityId();
            }
        }

        foreach ($connectedClaims as $connectedClaim) {
            $identity = $connectedClaim->getIdentity();
            if ($identity->getIdentityId() === $initialIdentityId) {
                return $identity;
            }
        }

        return $this->createIdentity($initialIdentityId);
    }

    private function createIdentity(string $identityId): Identity
    {
        $identity = new Identity();
        $identity->setIdentityId($identityId);
        $this->persist($identity);

        return $identity;
    }

    #[Teardown]
    public function teardown(): void
    {
        $this->getEntityManager()->createQuery('DELETE FROM ' . ClaimPresentation::class)->execute();
        $this->getEntityManager()->createQuery('DELETE FROM ' . ClaimConnection::class)->execute();
        $this->getEntityManager()->createQuery('DELETE FROM ' . Claim::class)->execute();
        $this->getEntityManager()->createQuery('DELETE FROM ' . Identity::class)->execute();
    }
}
