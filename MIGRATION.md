# Migration et mise à jour des permissions

## Exécuter la migration depuis Docker

Pour appliquer les changements de permissions, exécutez ces commandes depuis votre conteneur Docker :

```bash
# Se connecter au conteneur
docker-compose exec app bash

# Option 1 : Migration complète (réinitialise TOUTE la base)
php artisan migrate:fresh --seed

# Option 2 : Juste mettre à jour les permissions (recommandé si vous avez des données)
php artisan db:seed --class=SecurityRoleSeeder
```

## Vérification après migration

Connectez-vous avec les comptes de test :

### Compte Admin
- **Identifiant** : `admin`
- **Mot de passe** : `admin`
- **Permissions** : Toutes (modifier, diffuser, supprimer, administrer)

### Compte Professeur
- **Identifiant** : `Jean Dupont` (ou `Sophie Martin`, `Pierre Bernard`)
- **Mot de passe** : `password`
- **Permissions** : modifier video, diffuser video, administrer site

### Compte Élève (à créer depuis l'admin)
- **Permissions** : diffuser video uniquement

## Corrections appliquées

✅ **Routes protégées** : Vérification dans le contrôleur pour admin ET professeur
✅ **Sidebar documentation** : Section admin visible pour admin ET professeur
✅ **Menu header** : Lien Administration visible pour admin ET professeur
✅ **Permissions** :
   - Admin : toutes les permissions
   - Professeur : modifier, diffuser, administrer
   - Élève : diffuser uniquement
