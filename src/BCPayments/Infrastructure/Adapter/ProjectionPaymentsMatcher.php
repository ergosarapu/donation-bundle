<?php

declare(strict_types=1);

namespace ErgoSarapu\DonationBundle\BCPayments\Infrastructure\Adapter;

use DateInterval;
use Doctrine\ORM\EntityManagerInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentMatch;
use ErgoSarapu\DonationBundle\BCPayments\Application\Port\PaymentsMatcherInterface;
use ErgoSarapu\DonationBundle\BCPayments\Application\Query\Model\Payment;
use ErgoSarapu\DonationBundle\BCPayments\Domain\Payment\PaymentImportStatus;
use ErgoSarapu\DonationBundle\SharedKernel\Identifier\PaymentId;

class ProjectionPaymentsMatcher implements PaymentsMatcherInterface
{
    /** @var array<PaymentMatchRule> */
    private array $rules;

    public function __construct(
        private readonly EntityManagerInterface $projectionEntityManager,
    ) {
        $this->rules = $this->buildRules();
    }

    public function match(PaymentId $paymentId): array
    {
        /** @var Payment|null $rootPayment */
        $rootPayment = $this->projectionEntityManager->getRepository(Payment::class)->find($paymentId->toString());
        if ($rootPayment === null) {
            return [];
        }

        $rootDate = $rootPayment->getEffectiveDate();
        // Allow quite long time window (e.g. card payment + 5 days long holiday = bank transfer)
        $startDate = $rootDate->sub(new DateInterval('P5D'));
        $endDate = $rootDate->add(new DateInterval('P5D'));

        $qb = $this->projectionEntityManager->createQueryBuilder();
        $qb->select('p')
            ->from(Payment::class, 'p')
            ->andWhere('p.paymentId != :paymentId')
            ->andWhere('p.amount = :amount')
            ->andWhere('p.currency = :currency')
            ->andWhere('(p.importStatus IS NULL OR p.importStatus = :acceptedStatus)')
            ->andWhere('(p.effectiveDate BETWEEN :startDate AND :endDate)')
            ->setParameter('paymentId', $paymentId->toString())
            ->setParameter('amount', $rootPayment->getAmount())
            ->setParameter('currency', $rootPayment->getCurrency())
            ->setParameter('acceptedStatus', PaymentImportStatus::Accepted->value)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        /** @var list<Payment> $candidates */
        $candidates = $qb->getQuery()->getResult();

        $matches = [];
        foreach ($candidates as $candidate) {
            [$score, $ruleScores] = $this->computeWeightedScore($rootPayment, $candidate);

            $matches[] = new PaymentMatch(
                $candidate,
                $score,
                $ruleScores,
            );
        }

        usort(
            $matches,
            static fn (PaymentMatch $left, PaymentMatch $right): int => $right->score <=> $left->score
        );

        $matches = array_values(array_filter(
            $matches,
            static fn (PaymentMatch $match): bool => $match->score >= 0.7
        ));

        return $matches;
    }

    /**
     * @return array{0: float, 1: array<string, float>}
     */
    private function computeWeightedScore(
        Payment $root,
        Payment $candidate
    ): array {
        $weightedSum = 0.0;
        $weightTotal = 0.0;
        $ruleScores = [];

        foreach ($this->rules as $rule) {
            $score = $rule->score($root, $candidate);
            $weight = $rule->weightFor($root, $candidate);
            if ($weight <= 0) {
                continue;
            }
            $ruleScores[$rule->name()] = $score;
            $weightedSum += $score * $weight;
            $weightTotal += $weight;
        }

        if ($weightTotal <= 0.0) {
            return [0.0, $ruleScores];
        }

        $mean = $weightedSum / $weightTotal;
        $finalScore = max(0.0, min(1.0, $mean));
        return [$finalScore, $ruleScores];
    }

    /**
     * @return list<PaymentMatchRule>
     */
    private function buildRules(): array
    {
        return [
            new LegacyPaymentNumberRule(),
            new ReferenceMatchRule(),
            new IbanMatchRule(),
            new EffectiveIdCodeMatchRule(),
            new EffectiveNameMatchRule(),
            new DescriptionSimilarityRule(),
            new DateProximityRule(),
        ];
    }
}

interface PaymentMatchRule
{
    public function name(): string;

    public function score(Payment $root, Payment $candidate): float;

    public function weightFor(Payment $root, Payment $candidate): float;
}

final class LegacyPaymentNumberRule implements PaymentMatchRule
{
    public function name(): string
    {
        return 'legacy_payment_number';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $candidateLegacyPaymentNumber = $candidate->getLegacyPaymentNumber();
        $rootDescription = $root->getDescription();

        if ($candidateLegacyPaymentNumber !== null && $rootDescription !== null && str_contains($rootDescription, $candidateLegacyPaymentNumber)) {
            return 1.0;
        }

        return 0.0;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        if ($candidate->getLegacyPaymentNumber() === null) {
            return 0.0;
        }
        return 10.0;
    }
}

final class ReferenceMatchRule implements PaymentMatchRule
{
    use StringNormalizer;

    public function name(): string
    {
        return 'reference';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $rootReference = $this->normalize($root->getReference());
        $candidateReference = $this->normalize($candidate->getReference());
        if ($rootReference === null || $candidateReference === null) {
            return 0.0;
        }

        return $rootReference === $candidateReference ? 1.0 : 0.0;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        if ($this->normalize($root->getReference()) === null || $this->normalize($candidate->getReference()) === null) {
            return 0.0;
        }
        return 7.0;
    }
}

final class IbanMatchRule implements PaymentMatchRule
{
    use StringNormalizer;

    public function name(): string
    {
        return 'iban';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $rootIban = $this->normalize($root->getIban());
        $candidateIban = $this->normalize($candidate->getIban());
        if ($rootIban === null || $candidateIban === null) {
            return 0.0;
        }

        return $rootIban === $candidateIban ? 1.0 : 0.0;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        if ($this->normalize($root->getIban()) === null || $this->normalize($candidate->getIban()) === null) {
            return 0.0;
        }
        return 6.0;
    }
}

final class NameMatchRule implements PaymentMatchRule
{
    use StringNormalizer;

    public function name(): string
    {
        return 'name';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $rootName = $this->resolveName($root);
        $candidateName = $this->resolveName($candidate);
        if ($rootName === null || $candidateName === null) {
            return 0.0;
        }

        if ($rootName === $candidateName) {
            return 1.0;
        }

        if (str_contains($candidateName, $rootName) || str_contains($rootName, $candidateName)) {
            return 1.0;
        }

        return 0.0;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        if ($this->resolveName($root) === null || $this->resolveName($candidate) === null) {
            return 0.0;
        }
        return 3.0;
    }

    private function resolveName(Payment $payment): ?string
    {
        $accountHolder = $payment->getAccountHolderName();
        if ($accountHolder !== null) {
            return $this->normalize($accountHolder);
        }

        $given = $payment->getGivenName();
        $family = $payment->getFamilyName();
        if ($given !== null || $family !== null) {
            return $this->normalize(trim(($given ?? '') . ' ' . ($family ?? '')));
        }

        return null;
    }
}

final class EffectiveIdCodeMatchRule implements PaymentMatchRule
{
    use StringNormalizer;

    public function name(): string
    {
        return 'effective_id_code';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $rootIdCode = $this->normalize($root->getEffectiveIdCode());
        $candidateIdCode = $this->normalize($candidate->getEffectiveIdCode());

        if ($rootIdCode === null || $candidateIdCode === null) {
            return 0.0;
        }

        return $rootIdCode === $candidateIdCode ? 1.0 : 0.0;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        if ($this->normalize($root->getEffectiveIdCode()) === null || $this->normalize($candidate->getEffectiveIdCode()) === null) {
            return 0.0;
        }
        return 8.0;
    }
}

final class EffectiveNameMatchRule implements PaymentMatchRule
{
    use StringNormalizer;

    public function name(): string
    {
        return 'effective_name';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $rootName = $this->normalize($root->getEffectiveName());
        $candidateName = $this->normalize($candidate->getEffectiveName());

        if ($rootName === null || $candidateName === null) {
            return 0.0;
        }

        // Exact match
        if ($rootName === $candidateName) {
            return 1.0;
        }

        // Full substring match
        if (str_contains($candidateName, $rootName) || str_contains($rootName, $candidateName)) {
            return 0.95;
        }

        // Tokenize both names
        $rootTokens = $this->tokenize($rootName);
        $candidateTokens = $this->tokenize($candidateName);

        if (empty($rootTokens) || empty($candidateTokens)) {
            return 0.0;
        }

        // Calculate Jaccard similarity
        $intersection = count(array_intersect($rootTokens, $candidateTokens));
        $union = count(array_unique(array_merge($rootTokens, $candidateTokens)));

        $similarity = $intersection / $union;

        // Bonus for matching all tokens from the shorter name
        $minTokenCount = min(count($rootTokens), count($candidateTokens));
        if ($intersection === $minTokenCount) {
            $similarity = max($similarity, 0.85);
        }

        return $similarity;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        if ($this->normalize($root->getEffectiveName()) === null || $this->normalize($candidate->getEffectiveName()) === null) {
            return 0.0;
        }
        return 4.0;
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $name): array
    {
        // Split by whitespace and common separators
        $tokens = preg_split('/[\s,\.]+/', $name, -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens === false) {
            return [];
        }

        // Filter out very short tokens (likely initials or noise)
        return array_values(array_filter($tokens, static fn ($token) => mb_strlen($token) >= 2));
    }
}

final class DescriptionSimilarityRule implements PaymentMatchRule
{
    use StringNormalizer;

    public function name(): string
    {
        return 'description';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $rootDescription = $this->normalize($root->getDescription());
        $candidateDescription = $this->normalize($candidate->getDescription());
        if ($rootDescription === null || $candidateDescription === null) {
            return 0.0;
        }

        // Exact match
        if ($rootDescription === $candidateDescription) {
            return 1.0;
        }

        // Full substring match
        if (str_contains($candidateDescription, $rootDescription) || str_contains($rootDescription, $candidateDescription)) {
            return 0.95;
        }

        // Tokenize both descriptions
        $rootTokens = $this->tokenize($rootDescription);
        $candidateTokens = $this->tokenize($candidateDescription);

        if (empty($rootTokens) || empty($candidateTokens)) {
            return 0.0;
        }

        // Calculate Jaccard similarity
        $intersection = count(array_intersect($rootTokens, $candidateTokens));
        $union = count(array_unique(array_merge($rootTokens, $candidateTokens)));

        // if ($union === 0) {
        //     return 0.0;
        // }

        $similarity = $intersection / $union;

        // Graduated scoring thresholds
        if ($similarity >= 0.8) {
            return max(0.85, min(0.94, 0.75 + ($similarity * 0.2)));
        }
        if ($similarity >= 0.6) {
            return max(0.6, min(0.84, 0.5 + ($similarity * 0.4)));
        }
        if ($similarity >= 0.4) {
            return max(0.3, min(0.59, $similarity * 1.2));
        }
        if ($similarity >= 0.2) {
            return max(0.1, min(0.29, $similarity * 1.0));
        }

        return 0.0;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        if ($this->normalize($root->getDescription()) === null || $this->normalize($candidate->getDescription()) === null) {
            return 0.0;
        }
        return 1.55;
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $description): array
    {
        // Split by whitespace and common separators
        $tokens = preg_split('/[\s,\.;:\-\/]+/', $description, -1, PREG_SPLIT_NO_EMPTY);
        if ($tokens === false) {
            return [];
        }

        // Filter out very short tokens
        return array_values(array_filter($tokens, static fn ($token) => mb_strlen($token) >= 2));
    }
}

trait StringNormalizer
{
    private function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $normalized = mb_strtolower(trim($value));
        return $normalized === '' ? null : $normalized;
    }
}

final class DateProximityRule implements PaymentMatchRule
{
    public function name(): string
    {
        return 'date';
    }

    public function score(Payment $root, Payment $candidate): float
    {
        $rootDate = $root->getEffectiveDate();
        $candidateDate = $candidate->getEffectiveDate();
        $diffSeconds = abs($rootDate->getTimestamp() - $candidateDate->getTimestamp());

        // Same day or within 12 hours
        if ($diffSeconds <= 43200) {
            return 1.0;
        }

        // Within 1 day
        if ($diffSeconds <= 86400) {
            return 0.9;
        }

        // Within 2 days
        if ($diffSeconds <= 172800) {
            return 0.7;
        }

        // Within 3 days (max range)
        if ($diffSeconds <= 259200) {
            return 0.5;
        }

        return 0.0;
    }

    public function weightFor(Payment $root, Payment $candidate): float
    {
        return 2.5;
    }
}
