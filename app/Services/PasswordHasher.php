<?php

namespace App\Services;

/**
 * Hachage et verification des mots de passe.
 *
 * Algorithme : PBKDF2-HMAC-SHA256.
 *
 * Justification du choix :
 *  - PBKDF2 est une fonction de derivation de cle normalisee (RFC 8018,
 *    approuvee par le NIST SP 800-63B) concue pour etre lente : son cout est
 *    regle par un nombre d'iterations, ce qui ralentit d'autant une attaque
 *    par force brute ou par dictionnaire hors ligne.
 *  - Elle expose explicitement le sel comme parametre d'entree, ce qu'exige le
 *    laboratoire (« un salt par utilisateur, visible lors de la demo »). Les
 *    fonctions password_hash() de PHP (bcrypt, Argon2id) genererent et
 *    encapsulent le sel dans l'empreinte, sans le rendre consultable.
 *
 * Limite assumee : Argon2id resisterait mieux aux attaques materielles (GPU,
 * ASIC) car il est aussi couteux en memoire, alors que PBKDF2 ne l'est qu'en
 * temps de calcul. Ce compromis est discute dans le rapport.
 */
class PasswordHasher
{
    /** Fonction de hachage sous-jacente utilisee par PBKDF2. */
    public const ALGORITHME = 'sha256';

    /** Taille du sel en octets (32 octets = 256 bits, soit 64 caracteres hexadecimaux). */
    public const LONGUEUR_SEL_OCTETS = 32;

    /** Taille de l'empreinte produite, en caracteres hexadecimaux. */
    public const LONGUEUR_EMPREINTE_HEX = 64;

    /**
     * Genere un sel aleatoire unique.
     *
     * random_bytes() s'appuie sur le generateur d'aleas cryptographique du
     * systeme d'exploitation. Un sel distinct par utilisateur rend les tables
     * arc-en-ciel inutilisables et empeche de deduire, en comparant deux
     * empreintes, que deux comptes partagent le meme mot de passe.
     *
     * @return string Le sel encode en hexadecimal (64 caracteres).
     *
     * @throws \Random\RandomException Si le systeme ne peut fournir d'aleas sur.
     */
    public function genererSel(): string
    {
        return bin2hex(random_bytes(self::LONGUEUR_SEL_OCTETS));
    }

    /**
     * Calcule l'empreinte PBKDF2 d'un mot de passe.
     *
     * @param  string  $motDePasse  Le mot de passe en clair.
     * @param  string  $sel  Le sel de l'utilisateur, en hexadecimal.
     * @param  int  $iterations  Nombre d'iterations PBKDF2 a appliquer.
     * @return string L'empreinte encodee en hexadecimal (64 caracteres).
     */
    public function hacher(string $motDePasse, string $sel, int $iterations): string
    {
        return hash_pbkdf2(
            self::ALGORITHME,
            $motDePasse,
            $sel,
            $iterations,
            self::LONGUEUR_EMPREINTE_HEX
        );
    }

    /**
     * Verifie qu'un mot de passe correspond a une empreinte connue.
     *
     * La comparaison utilise hash_equals(), dont le temps d'execution ne depend
     * pas de la position du premier caractere different. Un operateur === sort
     * de la boucle des la premiere difference : un attaquant pourrait alors
     * reconstituer l'empreinte caractere par caractere en mesurant le temps de
     * reponse du serveur (attaque temporelle).
     *
     * @param  string  $motDePasse  Le mot de passe en clair a verifier.
     * @param  string  $empreinteAttendue  L'empreinte enregistree.
     * @param  string  $sel  Le sel utilise lors du hachage d'origine.
     * @param  int  $iterations  Le nombre d'iterations utilise lors du hachage d'origine.
     * @return bool Vrai si le mot de passe correspond.
     */
    public function verifier(
        string $motDePasse,
        string $empreinteAttendue,
        string $sel,
        int $iterations
    ): bool {
        $empreinteCalculee = $this->hacher($motDePasse, $sel, $iterations);

        return hash_equals($empreinteAttendue, $empreinteCalculee);
    }

    /**
     * Effectue un hachage factice de cout equivalent.
     *
     * Appelee lorsque l'identifiant saisi ne correspond a aucun compte : sans
     * cela, une tentative sur un compte inexistant repondrait immediatement,
     * alors qu'une tentative sur un compte existant prendrait le temps du
     * PBKDF2. Cet ecart mesurable permettrait d'enumerer les identifiants
     * valides. On consomme donc le meme temps de calcul dans les deux cas.
     *
     * @param  int  $iterations  Nombre d'iterations, identique au cas nominal.
     * @return void
     */
    public function hachageFactice(int $iterations): void
    {
        $this->hacher('mot-de-passe-factice', str_repeat('0', self::LONGUEUR_EMPREINTE_HEX), $iterations);
    }
}
