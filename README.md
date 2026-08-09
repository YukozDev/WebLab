# GTI619 — Laboratoire 5 : module d'authentification

Application web Laravel 12 implémentant un contrôle d'accès RBAC, un module
d'authentification et une gestion de session sécurisée.

---

## 1. Prérequis

| Outil | Version | Note |
|---|---|---|
| PHP | **8.4** (minimum 8.2) | extensions `openssl`, `mbstring`, `fileinfo`, `pdo_sqlite`, `sqlite3`, `curl`, `zip` |
| Composer | 2.x | |
| SQLite | inclus dans PHP | aucun serveur de base de données à installer |

### Installer PHP sous Windows (sans droits administrateur)

1. Télécharger `php-8.4.x-Win32-vs17-x64.zip` sur <https://windows.php.net/download/>
   (version **Thread Safe**, x64).
2. Extraire dans un dossier, par exemple `C:\Users\<vous>\php`.
3. Dans ce dossier, copier `php.ini-development` vers `php.ini`, puis décommenter
   (retirer le `;` en début de ligne) :
   ```
   extension_dir = "ext"
   extension=openssl
   extension=mbstring
   extension=fileinfo
   extension=pdo_sqlite
   extension=sqlite3
   extension=curl
   extension=zip
   ```
4. Ajouter ce dossier à la variable d'environnement `Path` de l'utilisateur.
5. Vérifier : `php --version` doit répondre.

### Installer Composer

```bash
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php --install-dir=. --filename=composer.phar
```

> Vérifiez la signature SHA-384 du script avec celle publiée sur
> <https://composer.github.io/installer.sig> avant de l'exécuter.

---

## 2. Installation du projet

```bash
git clone <url-du-depot> gti619
cd gti619
composer install
cp .env.example .env
php artisan key:generate
```

Créer le fichier de base de données puis le peupler :

```bash
php artisan migrate:fresh --seed
```

Lancer l'application :

```bash
php artisan serve
```

L'application est accessible sur <http://localhost:8000>.

---

## 3. Comptes de démonstration

Créés par `UserSeeder`. Mots de passe volontairement documentés : ce sont des
comptes de démonstration sur une base locale.

| Identifiant | Mot de passe | Rôle | Accès |
|---|---|---|---|
| `Administrateur` | `Admin!GTI619#2024` | Administrateur | Tout |
| `Utilisateur1` | `Residentiel!619#2024` | Préposé aux clients résidentiels | Clients résidentiels |
| `Utilisateur2` | `Affaire!619#2024` | Préposé aux clients d'affaire | Clients d'affaire |

Les comptes créés ensuite depuis l'interface d'administration reçoivent un mot
de passe temporaire généré aléatoirement, affiché **une seule fois**, et doivent
le changer à la première connexion.

---

## 4. Matrice des accès (RBAC)

| Route | Administrateur | Préposé résidentiel | Préposé affaire |
|---|:---:|:---:|:---:|
| `/clients/residentiels` | ✔ | ✔ | |
| `/clients/affaires` | ✔ | | ✔ |
| `/admin/utilisateurs` | ✔ | | |
| `/admin/parametres` | ✔ | | |

L'autorisation est appliquée par le middleware `role:` déclaré dans
[`routes/web.php`](routes/web.php). Le masquage des liens dans le menu n'est
qu'un confort d'affichage : l'accès direct par URL est refusé côté serveur
(page 403) et journalisé.

---

## 5. Organisation du code

```
app/
  Http/
    Controllers/
      Admin/SecuritySettingController.php   Paramètres de sécurité (admin)
      Admin/UserController.php              Création de comptes et rôles (admin)
      Auth/LoginController.php              Connexion, déconnexion
      Auth/PasswordController.php           Changement de mot de passe
      ClientController.php                  Listes de clients
      DashboardController.php               Accueil
    Middleware/
      CheckRole.php                         Point de contrôle RBAC
      EnsurePasswordIsCurrent.php           Changement de mot de passe imposé
  Models/
    AuthLog.php  Client.php  PasswordHistory.php  Role.php
    SecuritySetting.php  User.php
  Services/
    PasswordHasher.php    PBKDF2, sel, comparaison en temps constant
    PasswordManager.php   Cycle de vie du mot de passe, historique
    PasswordPolicy.php    Norme configurable, génération de mot de passe
database/
  migrations/   Schéma (rôles, utilisateurs, historique, journal, sessions)
  seeders/      Rôles, comptes, clients, paramètres par défaut
```

---

## 6. Travail en équipe

- **Ne jamais committer `.env`** : il contient `APP_KEY`, qui chiffre les cookies
  et les sessions. Le fichier est dans `.gitignore`. Chaque équipier part de
  `.env.example` et génère sa propre clé.
- **`database/database.sqlite` n'est pas versionné.** Après un `git pull`,
  exécuter `php artisan migrate` (ou `migrate:fresh --seed` pour repartir à
  neuf) plutôt que de s'échanger le fichier de base.
- **Migrations** : ne jamais modifier une migration déjà poussée sur le dépôt.
  En créer une nouvelle avec `php artisan make:migration`.
- Les migrations utilisent des **classes anonymes**, ce qui évite les collisions
  de noms de classes lorsque plusieurs personnes en créent en parallèle.
- `composer.json` épingle la plateforme PHP à 8.4 pour que tout le monde
  résolve exactement les mêmes versions de dépendances.

---

## 7. Choix techniques notables

**Laravel 12 plutôt que le squelette Laravel 8 fourni.** Composer refuse
d'installer Laravel 8, 10 et 11 : toutes les versions antérieures à 12.61.1
portent des avis de sécurité non corrigés (injection CRLF dans la règle de
validation `email`, confusion de chemin sur les URL signées). Passer outre
aurait exigé de désactiver le contrôle de sécurité de Composer, ce qui est
difficilement défendable dans un laboratoire de sécurité. Le code du squelette
(CRUD des clients) a été repris et enrichi.

**PBKDF2-HMAC-SHA256 plutôt que bcrypt ou Argon2id.** L'énoncé exige un sel par
utilisateur *visible lors de la démonstration*. Les fonctions `password_hash()`
de PHP génèrent et encapsulent le sel dans l'empreinte, sans l'exposer. PBKDF2
prend le sel en paramètre explicite, ce qui permet de le stocker et de
l'afficher. Argon2id serait préférable en production car il est aussi coûteux
en mémoire, ce qui résiste mieux aux attaques matérielles.

**Aucune ressource externe dans les pages.** Pas de CDN, pas de police
distante, pas de script tiers : la surface d'attaque par chaîne
d'approvisionnement est nulle et la démonstration fonctionne sans Internet.

---

## 8. État d'avancement

| Partie | Élément | État |
|---|---|:---:|
| 1 | Trois rôles, trois utilisateurs | ✔ |
| 1 | Page d'authentification | ✔ |
| 1 | Page admin des paramètres (admin seul) | ✔ |
| 1 | Page clients résidentiels | ✔ |
| 1 | Page clients d'affaire | ✔ |
| 1 | Ajout d'utilisateur avec rôle | ✔ |
| 2 | Stockage sécurisé (sel + itérations) | ✔ |
| 2 | Norme configurable de complexité | ✔ |
| 2 | Interdiction des X derniers mots de passe | ✔ |
| 2 | Changement imposé (création, expiration) | ✔ |
| 2 | Réauthentification (changement de mdp, ajout d'utilisateur) | ✔ |
| 2 | Journalisation des évènements de sécurité | ✔ |
| 2 | Nombre maximal de tentatives et blocage | à faire |
| 2 | Délai de blocage après un échec | à faire |
| 2 | Déblocage et réinitialisation par l'administrateur | à faire |
| 2 | Consultation du journal par l'administrateur | à faire |
| 2 | HTTPS (protocole de communication protégé) | à faire |
| 3 | Protection de l'identifiant de session | partiel (régénéré à la connexion) |
| 3 | Stockage sécurisé des sessions côté serveur | à faire (chiffrement, cookie `Secure`) |
