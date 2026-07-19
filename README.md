# Osteoclic

Application de gestion de patients pour un cabinet d'ostéopathie (fiches patients, consultations, antécédents, statistiques). Usage local uniquement, Symfony, un seul cabinet ou plusieurs cabinets géographiques.

## Stack

- Symfony **7.4 LTS**, PHP **8.3+** (minimum technique 8.1, mais l'appli est testée et livrée avec 8.3), Doctrine ORM
- Base de données **SQLite** (fichier unique `var/data.db`) — pas de serveur de base de données à faire tourner
- Serveur PHP intégré (pas de dépendance à Laragon/Apache/nginx pour l'usage courant)

## Démarrer / arrêter l'application (usage courant)

- **Démarrer** : double-clic sur `Osteoclic.bat` (ou `Osteoclic.vbs`). Aucune fenêtre ne s'ouvre, le navigateur se lance automatiquement sur http://127.0.0.1:8000/. Le script cherche PHP 8.3 automatiquement (chemin Laragon standard, sinon PATH).
- **Arrêter** : double-clic sur `Osteoclic-Stop.vbs`.

Laragon n'est pas nécessaire pour l'usage courant : l'application utilise le serveur PHP intégré et SQLite, plus besoin qu'un service de base de données tourne en arrière-plan.

## Fonctionnalités

- Gestion des patients (fiche complète : coordonnées, antécédents médicaux détaillés, historique des consultations)
- Consultations : anamnèse, test, traitement, conseil, note, moyen de paiement, montant
- Gestion de plusieurs cabinets et utilisateurs (comptes praticien/admin)
- Recherche de patients par nom/prénom/téléphone/adresse, **insensible aux accents** ("elise" trouve "Élise")
- Page **Statistiques** (`/statistiques`, réservée aux admins) : indicateurs simples en texte/tableaux (patients, consultations, chiffre d'affaires, démographie), tous cabinets confondus — pas de graphiques, choix délibéré
- Gestion des cabinets et des utilisateurs (CRUD, réservé aux admins)

## Installation depuis zéro (nouveau poste)

```bash
git clone https://github.com/Kikuichimonji/PROJET_ANNABEL_V3.git
cd PROJET_ANNABEL_V3
composer install
```

Créer un `.env.local` (non versionné) si besoin de surcharger la configuration par défaut de `.env` (notamment en développement, voir plus bas). Par défaut, `DATABASE_URL` pointe déjà vers `var/data.db` en SQLite.

Si aucune base n'existe encore (premier lancement sur un poste neuf, sans données à reprendre) :
```bash
php bin/console doctrine:schema:create
```

Sinon, voir la section **Migration depuis l'ancienne base MySQL** ci-dessous.

## Développement

- L'application tourne par défaut en mode `prod` (`APP_ENV=prod` dans `.env`) : pas de recompilation automatique des templates, pages d'erreur propres. C'est le mode à utiliser en usage réel.
- Pour développer/ajuster sans avoir à vider le cache après chaque modification, créer un `.env.local` avec `APP_ENV=dev` (Symfony recompile alors les templates à chaque rechargement). **Repasser en `prod`** (ou supprimer ce fichier) avant un usage réel par l'utilisatrice.
- Après un changement en mode `prod`, vider le cache manuellement : `rm -rf var/cache/prod`.

## Tests

```bash
php vendor/bin/simple-phpunit
```

19 tests couvrent : la suppression de patient (non-régression sur les cascades Doctrine), la recherche (combinaison texte/cabinet, insensibilité aux accents), le calcul des statistiques (cas piégeux : nouveau patient vs patient récurrent, casse incohérente des données), et un test de fumée sur les routes principales. À faire passer avant tout changement livré.

## Sauvegarde des données

Toutes les données sont dans un seul fichier : `var/data.db`. Pour sauvegarder, il suffit de copier ce fichier ailleurs (clé USB, cloud...) pendant que l'application est arrêtée.

## Migration depuis l'ancienne base MySQL (à faire une seule fois, par poste)

Cette procédure concerne un poste qui tournait encore l'ancienne version de l'application (Symfony 5.1 / PHP 7.4 / MySQL via Laragon) et dont on veut reprendre les vraies données dans la nouvelle version.

**Sauvegarder d'abord.** Avant toute action, exporter la base MySQL complète :
```bash
mysqldump -u root NOM_DE_LA_BASE > sauvegarde_avant_migration_AAAA-MM-JJ.sql
```
Garder ce fichier en lieu sûr tant que la nouvelle version n'a pas été validée en usage réel.

**Vérifier la correspondance de schéma avant de migrer.** Les migrations Doctrine versionnées de ce dépôt (`migrations/`) ne couvrent qu'une partie de l'historique réel du schéma — une partie de son évolution (ajout de la table `moyen_paiement`, des clés étrangères `consultation.utilisateur_id`/`consultation.moyen_paiement_id` [obligatoires], passage des antécédents médicaux d'un modèle à deux tables `antecedent`/`ant_detail` vers des colonnes directes `ant_*` sur `patient`) a été appliquée hors migration tracée. **Ne pas supposer que la base MySQL source correspond exactement au schéma cible actuel** (décrit par les entités dans `src/Entity/`) sans l'avoir vérifié :

```sql
SHOW TABLES;
DESCRIBE patient;       -- colonnes ant_tete, ant_orl, ... presentes ?
DESCRIBE consultation;  -- colonnes utilisateur_id, moyen_paiement_id, cabinet_id presentes ?
SELECT COUNT(*) FROM ant_detail;  -- si cette table existe encore et contient des lignes,
                                   -- il peut y avoir de l'historique d'antecedents qui ne
                                   -- sera pas repris automatiquement (voir plus bas)
```

Si le schéma source correspond au schéma cible, la migration automatique fonctionnera directement (étapes ci-dessous). Sinon, corriger l'écart côté MySQL avant de continuer (ajouter les colonnes/tables manquantes, ou migrer manuellement les données concernées) plutôt que de forcer la migration automatique.

**Lancer la migration** (sur le poste où tournait l'ancienne base MySQL/Laragon, avec le nouveau code déjà installé et `composer install` fait) :

```bash
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all
php bin/console app:migrate-to-sqlite
```

La dernière commande lit l'URL MySQL source dans `MYSQL_SOURCE_URL` (définie dans `.env`, à adapter si l'utilisateur/mot de passe/nom de base MySQL diffère de la valeur par défaut) et copie les données vers `var/data.db`, table par table, en suivant l'ordre des dépendances (clés étrangères) du schéma **cible**. Une table présente côté MySQL mais absente du schéma cible (ex. l'ancienne table `consult_calendar`, ou `antecedent`/`ant_detail` si elles existent encore) est silencieusement ignorée — normal pour ce qui est réellement obsolète, mais c'est justement pour ça que la vérification de schéma ci-dessus est nécessaire avant de lancer cette commande sur de vraies données.

Si une table cible contient déjà des données, la commande refuse de continuer sans l'option `--force` (qui vide alors la table avant de la remplir) — ne l'utiliser qu'en connaissance de cause.

**Vérifier après migration** : comparer les comptages de lignes entre l'ancienne base et la nouvelle, table par table (`patient`, `consultation`, `cabinet`, `utilisateur`), et ouvrir quelques fiches patients au hasard pour comparer visuellement avec l'ancienne version avant de considérer la migration terminée.

## Revenir à l'ancienne base MySQL (rollback)

L'ancienne configuration MySQL est conservée en commentaire dans `.env` (`DATABASE_URL`). Le dump `annabel.sql` correspond à l'ancienne base. En cas de souci, redémarrer Laragon, décommenter la ligne MySQL dans `.env`, commenter la ligne SQLite, et relancer avec `symfony serve` comme avant (nécessite l'ancienne version du code, PHP 7.4/Symfony 5.1 — pas ce dépôt).
