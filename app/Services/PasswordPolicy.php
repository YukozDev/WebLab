<?php

namespace App\Services;

use App\Models\SecuritySetting;
use Illuminate\Validation\Rules\Password;

/**
 * Norme configurable applicable aux mots de passe.
 *
 * Les exigences ne sont pas ecrites en dur : elles sont lues dans la table
 * security_settings, que l'administrateur modifie depuis son interface. Une
 * seule classe construit les regles de validation, de sorte que le formulaire
 * de changement de mot de passe et celui de creation d'utilisateur appliquent
 * necessairement la meme politique.
 */
class PasswordPolicy
{
    /**
     * Caracteres consideres comme speciaux par la politique.
     */
    private const CARACTERES_SPECIAUX = '!@#$%^&*()-_=+[]{};:,.?';

    /**
     * Construit la regle de validation Laravel correspondant a la politique.
     *
     * @return \Illuminate\Validation\Rules\Password La regle a appliquer au champ mot de passe.
     */
    public function regle(): Password
    {
        $parametres = SecuritySetting::courants();

        $regle = Password::min($parametres->password_min_length);

        if ($parametres->require_uppercase || $parametres->require_lowercase) {
            // mixedCase() exige a la fois une majuscule et une minuscule ;
            // Laravel ne permet pas de n'en exiger qu'une seule des deux.
            $regle = $regle->mixedCase();
        }

        if ($parametres->require_digit) {
            $regle = $regle->numbers();
        }

        if ($parametres->require_special) {
            $regle = $regle->symbols();
        }

        // Refuse les mots de passe apparaissant dans les fuites publiques
        // connues, verifies via l'API k-anonymat de haveibeenpwned : seuls les
        // cinq premiers caracteres de l'empreinte SHA-1 quittent le serveur.
        // En cas d'indisponibilite du service, la regle laisse passer.
        return $regle->uncompromised();
    }

    /**
     * Decrit la politique en vigueur, pour affichage a l'utilisateur.
     *
     * @return array<int, string> Les exigences, une par ligne.
     */
    public function exigences(): array
    {
        $parametres = SecuritySetting::courants();

        $exigences = ["au moins {$parametres->password_min_length} caracteres"];

        if ($parametres->require_lowercase) {
            $exigences[] = 'au moins une lettre minuscule';
        }
        if ($parametres->require_uppercase) {
            $exigences[] = 'au moins une lettre majuscule';
        }
        if ($parametres->require_digit) {
            $exigences[] = 'au moins un chiffre';
        }
        if ($parametres->require_special) {
            $exigences[] = 'au moins un caractere special (' . self::CARACTERES_SPECIAUX . ')';
        }
        if ($parametres->password_history_count > 0) {
            $exigences[] = "different des {$parametres->password_history_count} derniers mots de passe utilises";
        }

        return $exigences;
    }

    /**
     * Genere un mot de passe temporaire conforme a la politique en vigueur.
     *
     * Utilise lors de la creation d'un compte et lors d'une reinitialisation
     * par l'administrateur. Le mot de passe est affiche une seule fois a
     * l'administrateur, puis l'utilisateur est contraint de le changer a sa
     * premiere connexion (indicateur must_change_password).
     *
     * Le tirage se fait avec random_int(), generateur cryptographiquement sur,
     * et non avec rand() ou array_rand() dont la suite est previsible.
     *
     * @return string Un mot de passe temporaire respectant la politique.
     *
     * @throws \Random\RandomException Si le systeme ne peut fournir d'aleas sur.
     */
    public function genererMotDePasseTemporaire(): string
    {
        $parametres = SecuritySetting::courants();

        $minuscules = 'abcdefghijkmnopqrstuvwxyz';
        $majuscules = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $chiffres = '23456789';
        $speciaux = self::CARACTERES_SPECIAUX;

        // Au moins un caractere de chaque classe exigee, pour garantir que le
        // mot de passe genere passe lui-meme la validation.
        $obligatoires = [
            $this->caractereAleatoire($minuscules),
            $this->caractereAleatoire($majuscules),
            $this->caractereAleatoire($chiffres),
            $this->caractereAleatoire($speciaux),
        ];

        // Au moins 16 caracteres, davantage si la politique l'exige.
        $longueur = max(16, $parametres->password_min_length);
        $alphabet = $minuscules . $majuscules . $chiffres . $speciaux;

        $caracteres = $obligatoires;
        for ($i = count($obligatoires); $i < $longueur; $i++) {
            $caracteres[] = $this->caractereAleatoire($alphabet);
        }

        // Melange pour que les caracteres obligatoires ne soient pas toujours
        // aux memes positions, ce qui reduirait l'entropie reelle.
        return $this->melanger($caracteres);
    }

    /**
     * Tire un caractere au hasard dans un alphabet, de facon cryptographiquement sure.
     *
     * @param  string  $alphabet  Les caracteres parmi lesquels tirer.
     * @return string Le caractere tire.
     *
     * @throws \Random\RandomException Si le systeme ne peut fournir d'aleas sur.
     */
    private function caractereAleatoire(string $alphabet): string
    {
        return $alphabet[random_int(0, strlen($alphabet) - 1)];
    }

    /**
     * Melange un tableau de caracteres (Fisher-Yates avec aleas cryptographique).
     *
     * shuffle() de PHP n'est pas utilise car il s'appuie sur le generateur
     * pseudo-aleatoire ordinaire, dont l'etat est predictible.
     *
     * @param  array<int, string>  $caracteres  Les caracteres a melanger.
     * @return string La chaine melangee.
     *
     * @throws \Random\RandomException Si le systeme ne peut fournir d'aleas sur.
     */
    private function melanger(array $caracteres): string
    {
        for ($i = count($caracteres) - 1; $i > 0; $i--) {
            $j = random_int(0, $i);
            [$caracteres[$i], $caracteres[$j]] = [$caracteres[$j], $caracteres[$i]];
        }

        return implode('', $caracteres);
    }
}
