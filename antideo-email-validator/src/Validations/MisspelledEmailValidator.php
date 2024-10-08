<?php

declare(strict_types=1);

namespace ADEV_EmailValidation\Validations;

defined('ABSPATH') or die('Nope nope nope...');

/**
 * Adapted from: https://github.com/GromNaN/MailCheck
 */
class MisspelledEmailValidator extends Validator implements ValidatorInterface
{
    const MINIMUM_WORD_DISTANCE_DOMAIN = 2;
    const MINIMUM_WORD_DISTANCE_TLD = 1;

    /**
     * @return string
     */
    public function getValidatorName()
    {
        return 'possible_email_correction'; // @codeCoverageIgnore
    }

    /**
     * @return string
     */
    public function getResultResponse()
    {
        if (!$this->getEmailAddress()->isValidEmailAddressFormat()) {
            return ''; // @codeCoverageIgnore
        }

        $suggestion = $this->findEmailAddressSuggestion();
        if ($suggestion === $this->getEmailAddress()->asString()) {
            return ''; // @codeCoverageIgnore
        }

        return $suggestion;
    }

    /**
     * @return string
     */
    private function findEmailAddressSuggestion()
    {
        if ($domainSuggestion = $this->findDomainSuggestion()) {
            return str_replace(
                $this->getEmailAddress()->getHostPart(),
                $domainSuggestion,
                $this->getEmailAddress()->asString()
            );
        }

        if ($topLevelDomainSuggestion = $this->findTopLevelDomainSuggestion()) {
            return str_replace(
                $this->getEmailAddress()->getTopLevelDomainPart(),
                $topLevelDomainSuggestion,
                $this->getEmailAddress()->asString()
            );
        }

        return '';
    }

    /**
     * @return bool|null|string
     */
    private function findTopLevelDomainSuggestion()
    {
        $topLevelDomain = $this->getEmailAddress()->getTopLevelDomainPart();
        $possibleTopLevelMatch = $this->findClosestWord(
            $topLevelDomain,
            $this->getEmailDataProvider()->getTopLevelDomains(),
            self::MINIMUM_WORD_DISTANCE_TLD
        );

        return $topLevelDomain === $possibleTopLevelMatch ? null : $possibleTopLevelMatch;
    }

    /**
     * @return bool|null|string
     */
    private function findDomainSuggestion()
    {
        $domain = $this->getEmailAddress()->getHostPart();
        $possibleMatch = $this->findClosestWord(
            $domain,
            $this->getEmailDataProvider()->getEmailProviders(),
            self::MINIMUM_WORD_DISTANCE_DOMAIN
        );

        return $domain === $possibleMatch ? null : $possibleMatch;
    }

    /**
     * @param string $stringToCheck
     * @param array $wordsToCheck
     * @param int $minimumDistance
     * @return string|bool
     */
    private function findClosestWord($stringToCheck, $wordsToCheck, $minimumDistance)
    {
        if (in_array($stringToCheck, $wordsToCheck)) {
            return $stringToCheck;
        }

        $closestMatch = '';
        foreach ($wordsToCheck as $testedWord) {
            $distance = levenshtein($stringToCheck, $testedWord);
            if ($distance <= $minimumDistance) {
                $minimumDistance = $distance - 1;
                $closestMatch = $testedWord;
            }
        }

        return $closestMatch;
    }
}
