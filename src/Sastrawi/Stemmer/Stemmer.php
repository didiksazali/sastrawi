<?php

namespace Sastrawi\Stemmer;

use Sastrawi\Dictionary\DictionaryInterface;

class Stemmer
{
    protected $dictionary;
    
    public function __construct(DictionaryInterface $dictionary)
    {
        $this->dictionary = $dictionary;
    }

    public function getDictionary()
    {
        return $this->dictionary;
    }
    
    /**
     * Stem a word to its common stem form
     *
     * @param string $word the word to stem, e.g : mengalahkan
     * @return string common stem form, e.g : kalah
     */
    public function stem($word)
    {
        if ($this->isShortWord($word)) {
            return $word;
        }
        
        $lookupResult = $this->dictionary->lookup($word);
        if ($lookupResult !== null) {
            return $lookupResult;
        }

        $stemmedWord = $this->removeInflectionalParticle($word);
        $lookupResult = $this->dictionary->lookup($stemmedWord);
        if ($lookupResult !== null) {
            return $lookupResult;
        }

        $stemmedWord = $this->removeInflectionalPossessivePronoun($stemmedWord);
        $lookupResult = $this->dictionary->lookup($stemmedWord);
        if ($lookupResult !== null) {
            return $lookupResult;
        }

        $stemmedWord = $this->removeDerivationalSuffix($stemmedWord);
        $lookupResult = $this->dictionary->lookup($stemmedWord);
        if ($lookupResult !== null) {
            return $lookupResult;
        }

        $stemmedWord = $this->removePlainPrefix($stemmedWord);
        $lookupResult = $this->dictionary->lookup($stemmedWord);
        if ($lookupResult !== null) {
            return $lookupResult;
        }

        $disambiguated = $this->disambiguatePrefixRule1a($stemmedWord);
        if ($disambiguated !== null) {
            $lookupResult = $this->dictionary->lookup($disambiguated);
            if ($lookupResult !== null) {
                return $lookupResult;
            }
        }

        $disambiguated = $this->disambiguatePrefixRule1b($stemmedWord);
        if ($disambiguated !== null) {
            $lookupResult = $this->dictionary->lookup($disambiguated);
            if ($lookupResult !== null) {
                return $lookupResult;
            }
        }

        $disambiguated = $this->disambiguatePrefixRule2($stemmedWord);
        if ($disambiguated !== null) {
            $lookupResult = $this->dictionary->lookup($disambiguated);
            if ($lookupResult !== null) {
                return $lookupResult;
            }
        }

        return $stemmedWord;
    }

    protected function isShortWord($word)
    {
        return (strlen($word) <= 3);
    }
    
    /**
     * Remove inflectional particle : lah|kah|tah|pun
     */
    public function removeInflectionalParticle($word)
    {
        return preg_replace('/(lah|kah|tah|pun)$/', '', $word, 1);
    }

    /**
     * Remove inflectional particle : ku|mu|nya
     */
    public function removeInflectionalPossessivePronoun($word)
    {
        return preg_replace('/(ku|mu|nya)$/', '', $word, 1);
    }
    
    /**
     * Remove derivational suffix : i|kan|an
     */
    public function removeDerivationalSuffix($word)
    {
        return preg_replace('/(i|kan|an)$/', '', $word, 1);
    }

    /**
     * Get removed affix
     */
    public function getRemovedAffix($completeWord, $wordAfterRemoved)
    {    
        return preg_replace("/$wordAfterRemoved/", '', $completeWord, 1);
    }

    /**
     * Remove plain prefix : di|ke|se
     */
    public function removePlainPrefix($word)
    {
        return preg_replace('/^(di|ke|se)/', '', $word, 1);
    }

    /**
     * Does the word contain invalid affix pair?
     * ber-i|di-an|ke-i|ke-an|me-an|ter-an|per-an
     */
    public function containsInvalidAffixPair($word)
    {
        if (preg_match('/^me(.*)kan$/', $word) === 1) {
            return false;
        }

        if ($word == 'ketahui') {
            return false;
        }

        $contains = false 
                    || preg_match('/^ber(.*)i$/', $word) === 1
                    || preg_match('/^di(.*)an$/', $word) === 1
                    || preg_match('/^ke(.*)i$/', $word) === 1
                    || preg_match('/^ke(.*)an$/', $word) === 1
                    || preg_match('/^me(.*)an$/', $word) === 1
                    || preg_match('/^ter(.*)an$/', $word) === 1
                    || preg_match('/^per(.*)an$/', $word) === 1;

        return $contains;
    }

    /**
     * Disambiguate Prefix Rule 1a
     * Rule 1 : berV -> ber-V
     */
    public function disambiguatePrefixRule1a($word)
    {
        $matches  = null;
        $contains = preg_match('/ber([aiueo].*)/', $word, $matches);

        if ($contains === 1) {
            return $matches[1];
        }
    }

    /**
     * Disambiguate Prefix Rule 1b
     * Rule 1 : berV -> be-rV
     */
    public function disambiguatePrefixRule1b($word)
    {
        $matches  = null;
        $contains = preg_match('/ber([aiueo].*)/', $word, $matches);

        if ($contains === 1) {
            return 'r' . $matches[1];
        }
    }

    /**
     * Disambiguate Prefix Rule 2
     * Rule 1 : berCAP -> ber-CAP where C != 'r' AND P != 'er'
     */
    public function disambiguatePrefixRule2($word)
    {
        $matches  = null;
        $contains = preg_match('/ber([bcdfghjklmnpqrstvwxyz])([aiueo])(.*)/', $word, $matches);

        if ($contains === 1) {
            if (preg_match('/^er(.*)$/', $matches[3]) === 1) {
                return;
            }
            
            return $matches[1] . $matches[2] . $matches[3];
        }
    }

}
