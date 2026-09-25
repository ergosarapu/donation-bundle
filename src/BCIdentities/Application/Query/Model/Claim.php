<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

use LogicException;

class Claim
{
    private string $claimId;
    private string $initialIdentityId;
    private string $sourceContext;
    private string $sourceId;
    private ?string $sourceType = null;
    /** @var iterable<int, ClaimConnection> */
    private iterable $connections = [];
    /** @var iterable<int, ClaimPresentation> */
    private iterable $presentations = [];

    private Identity $identity;

    public function getSourceContext(): string
    {
        return $this->sourceContext;
    }

    public function setSourceContext(string $sourceContext): void
    {
        $this->sourceContext = $sourceContext;
    }

    public function getSourceType(): ?string
    {
        return $this->sourceType;
    }

    public function setSourceType(?string $sourceType): void
    {
        $this->sourceType = $sourceType;
    }

    public function getClaimId(): string
    {
        return $this->claimId;
    }

    public function setClaimId(string $claimId): void
    {
        $this->claimId = $claimId;
    }

    public function getInitialIdentityId(): string
    {
        return $this->initialIdentityId;
    }

    public function setInitialIdentityId(string $initialIdentityId): void
    {
        if (isset($this->initialIdentityId) && $this->initialIdentityId !== $initialIdentityId) {
            throw new LogicException('The initial identity ID cannot be changed.');
        }

        $this->initialIdentityId = $initialIdentityId;
    }

    public function getIdentityId(): string
    {
        return $this->identity->getIdentityId();
    }

    public function getIdentity(): Identity
    {
        return $this->identity;
    }

    public function setIdentity(Identity $identity): void
    {
        $this->identity = $identity;
    }

    /**
     * @return list<string>
     */
    public function getConnectedClaimIds(): array
    {
        $result = [];
        foreach ($this->connections as $connection) {
            $result[$connection->getConnectedClaimId()] = $connection->getConnectedClaimId();
        }

        return array_values($result);
    }

    public function addConnection(string $connectedClaimId, string $reason): void
    {
        foreach ($this->connections as $connection) {
            if (
                $connection->getConnectedClaimId() === $connectedClaimId
                && $connection->getReason() === $reason
            ) {
                return;
            }
        }

        $this->appendToCollection($this->connections, new ClaimConnection($this, $connectedClaimId, $reason));
    }

    public function removeConnection(string $connectedClaimId, string $reason): void
    {
        foreach ($this->connections as $connection) {
            if (
                $connection->getConnectedClaimId() !== $connectedClaimId
                || $connection->getReason() !== $reason
            ) {
                continue;
            }

            $this->removeFromCollection($this->connections, $connection);
            return;
        }
    }

    public function getSourceId(): string
    {
        return $this->sourceId;
    }

    public function setSourceId(string $sourceId): void
    {
        $this->sourceId = $sourceId;
    }

    public function getPresentationForEvidenceLevel(?string $evidenceLevel): ?ClaimPresentation
    {
        foreach ($this->presentations as $presentation) {
            if ($presentation->getEvidenceLevel() === $evidenceLevel) {
                return $presentation;
            }
        }

        return null;
    }

    public function addPresentation(?string $evidenceLevel): ClaimPresentation
    {
        $presentation = new ClaimPresentation($this, $evidenceLevel);
        $this->appendToCollection($this->presentations, $presentation);

        return $presentation;
    }

    public function clearPresentations(): void
    {
        if (is_array($this->presentations)) {
            $this->presentations = [];

            return;
        }

        if (method_exists($this->presentations, 'clear')) {
            $this->presentations->clear();
        }
    }

    /**
     * @return list<ClaimPresentation>
     */
    public function getPresentations(): array
    {
        $result = [];
        foreach ($this->presentations as $presentation) {
            $result[] = $presentation;
        }

        return $result;
    }

    /**
     * @return list<string>
     */
    public function getPresentationsSummary(): array
    {
        return array_map(
            static function (ClaimPresentation $presentation): string {
                $parts = array_filter([
                    $presentation->getEvidenceLevel(),
                    $presentation->getGivenName() !== null || $presentation->getFamilyName() !== null
                        ? trim(sprintf('%s %s', $presentation->getGivenName() ?? '', $presentation->getFamilyName() ?? ''))
                        : null,
                    $presentation->getRawName(),
                    $presentation->getEmail(),
                    $presentation->getIban(),
                    $presentation->getLegalIdentifier(),
                ]);

                return implode(' | ', $parts);
            },
            $this->getPresentations(),
        );
    }

    /**
     * @template T of object
     * @param iterable<int, T> $items
     * @param T $item
     */
    private function appendToCollection(iterable &$items, object $item): void
    {
        if (is_array($items)) {
            $items[] = $item;

            return;
        }

        if (method_exists($items, 'add')) {
            $items->add($item);
        }
    }

    /**
     * @template T of object
     * @param iterable<int, T> $items
     * @param T $item
     */
    private function removeFromCollection(iterable &$items, object $item): void
    {
        if (is_array($items)) {
            $items = array_values(array_filter(
                $items,
                static fn (object $existingItem): bool => $existingItem !== $item,
            ));

            return;
        }

        if (method_exists($items, 'removeElement')) {
            $items->removeElement($item);
        }
    }
}
