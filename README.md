# Osteoclic

Application Symfony (usage local uniquement).

## Demarrer / arreter l'application

- **Demarrer** : double-clic sur `Osteoclic.bat` (ou `Osteoclic.vbs`). Aucune fenetre ne s'ouvre, le navigateur se lance automatiquement sur http://127.0.0.1:8000/.
- **Arreter** : double-clic sur `Osteoclic-Stop.vbs`.

Laragon n'est plus necessaire pour l'usage courant : l'application utilise le serveur PHP integre et une base **SQLite** (fichier `var/data.db`), plus besoin qu'un service MySQL tourne en arriere-plan.

## Sauvegarde des donnees

Toutes les donnees sont dans un seul fichier : `var/data.db`. Pour sauvegarder, il suffit de copier ce fichier ailleurs (cle USB, cloud...) pendant que l'application est arretee.

## Revenir a l'ancienne base MySQL (rollback)

L'ancienne configuration MySQL est conservee en commentaire dans `.env` (`DATABASE_URL`). Le dump `annabel.sql` correspond a l'ancienne base. En cas de souci, on peut redemarrer Laragon, decommenter la ligne MySQL dans `.env`, recommenter la ligne SQLite, et relancer avec `symfony serve` comme avant.

## Migration initiale MySQL -> SQLite (a faire une seule fois)

Sur la machine ou tournait l'ancienne base MySQL/Laragon :

```
composer install
php bin/console doctrine:schema:create
php bin/console doctrine:migrations:sync-metadata-storage
php bin/console doctrine:migrations:version --add --all
php bin/console app:migrate-to-sqlite
```

La derniere commande lit l'URL MySQL source dans `MYSQL_SOURCE_URL` (definie dans `.env`) et copie toutes les donnees vers `var/data.db`. Faire un `mysqldump` de sauvegarde avant, par precaution.
