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

## État d'avancement

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
