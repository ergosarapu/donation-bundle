<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCIdentities\Application\Query\Model;

class Claim
{
    private string $claimId;
    private string $sourceContext;
    private string $sourceId;
    private ?string $sourceType = null;
    /** @var iterable<int, ClaimCorrelatedSource> */
    private iterable $correlatedSources = [];
    /** @var iterable<int, ClaimLinkedClaim> */
    private iterable $linkedClaims = [];
    /** @var iterable<int, ClaimPresentation> */
    private iterable $presentations = [];

    private ?string $identityId = null;

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

    public function getIdentityId(): ?string
    {
        return $this->identityId;
    }

    public function setIdentityId(?string $identityId): void
    {
        $this->identityId = $identityId;
    }

    /**
     * @return array<string>
     */
    public function getCorrelatedSourceIds(): array
    {
        $result = [];
        foreach ($this->correlatedSources as $correlatedSource) {
            $result[] = $correlatedSource->getCorrelatedSourceId();
        }

        return $result;
    }

    public function addCorrelatedSource(string $correlatedSourceId): void
    {
        foreach ($this->correlatedSources as $correlatedSource) {
            if ($correlatedSource->getCorrelatedSourceId() === $correlatedSourceId) {
                return;
            }
        }

        $this->appendToCollection($this->correlatedSources, new ClaimCorrelatedSource($this, $correlatedSourceId));
    }

    /**
     * @return list<string>
     */
    public function getLinkedClaimIds(): array
    {
        $result = [];
        foreach ($this->linkedClaims as $correlatedClaim) {
            $result[$correlatedClaim->getLinkedClaimId()] = $correlatedClaim->getLinkedClaimId();
        }

        return array_values($result);
    }

    public function addLinkedClaim(string $linkedClaimId): void
    {
        foreach ($this->linkedClaims as $correlatedClaim) {
            if ($correlatedClaim->getLinkedClaimId() === $linkedClaimId) {
                return;
            }
        }

        $this->appendToCollection($this->linkedClaims, new ClaimLinkedClaim($this, $linkedClaimId));
    }

    public function removeLinkedClaim(string $linkedClaimId): void
    {
        if (is_array($this->linkedClaims)) {
            $this->linkedClaims = array_values(array_filter(
                $this->linkedClaims,
                static fn (ClaimLinkedClaim $correlatedClaim): bool => $correlatedClaim->getLinkedClaimId() !== $linkedClaimId,
            ));

            return;
        }

        foreach ($this->linkedClaims as $correlatedClaim) {
            if ($correlatedClaim->getLinkedClaimId() !== $linkedClaimId) {
                continue;
            }

            if (method_exists($this->linkedClaims, 'removeElement')) {
                $this->linkedClaims->removeElement($correlatedClaim);
            }

            return;
        }
    }

    public function removeCorrelatedSourceId(string $correlatedSourceId): void
    {
        if (is_array($this->correlatedSources)) {
            $this->correlatedSources = array_values(array_filter(
                $this->correlatedSources,
                static fn (ClaimCorrelatedSource $correlatedSource): bool => $correlatedSource->getCorrelatedSourceId() !== $correlatedSourceId,
            ));

            return;
        }

        foreach ($this->correlatedSources as $correlatedSource) {
            if ($correlatedSource->getCorrelatedSourceId() !== $correlatedSourceId) {
                continue;
            }

            if (method_exists($this->correlatedSources, 'removeElement')) {
                $this->correlatedSources->removeElement($correlatedSource);
            }

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
}
