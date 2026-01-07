
# Media Asset Manager | BTSPlay

**BTSPlay** est le M.A.M. utilisé par le BTS Audiovisuel de Biarritz (Pôle son et image) et développé par une équipe d'étudiants de BUT3 Informatique à l'IUT de Bayonne.  
  
**Les membres de cette équipe sont :**
- BAROS Arthur
- HAMCHOUCHE Kamelia
- LOUSTAU-CAZAUX David 
- LUCAS Liam 
- NUNEZ RODRIGUEZ Laura Elena

## Les besoins du commanditaire
À partir de deux serveurs NAS présents sur site contenants les copies des projets vidéo du BTS dans deux formats différents (un NAS dédié au format .MXF et l'autre au .MP4), l'application doit pouvoir afficher, et eventuellment **réconcilier** le contenu de ces deux serveurs. BTSPlay doit aussi porposer la lecture des videos dans son interface ainsi que l'ajout de métadonnées.
  
  Cela implique donc de vérifier la présence d'un projet dans les formats (sur les deux NAS), et eventuellement transcoder un projet dans l'un ou l'autre des formats en cas d'absence du projet sur l'un des serveurs.

 Succinctement, BTSPlay répond aux besoins du commanditaire en proposant plusieurs fonctionnalités majeures :  
  
  - Découvrir et indexer les vidéos des serveurs NAS.
  - Visualiser les vidéos au format MP4 dans un lecteur vidéo, accessible pour tous les acteurs du lycée, y compris les étudiants.
  - Contacter l'API de FFAStrans installé sur un serveur adjacent afin d'encoder et de déplacer les vidéos sur le bon NAS dans les bons formats (.MXF et .MP4).
  - Mettre à disposition un systeme d'authentification.
  - Proposer un panneau d'administration aux professeurs authentifiés.



## déploiement local de l'environnement de developpement
Cette partie vise à installer l'environnement de développement complet sur un poste personnel :

**Prérequis**
- Git
- Docker desktop
- la dernière version du fichier .env du projet

**Deploiement**  
- Cloner le projet

```bash
  git clone https://github.com/brs64/Media-Asset-Manager
```

- Se déplacer dans le répertoire du projet

```bash
  cd Media-Asset-Manager
```

- **Copier le .env mis a disposition dans les canaux de communication interne.**  
- Lancer l'initialisation des conteneurs Docker

```bash
  docker compose up
```

- Attendre la fin du lancement des conteneurs, puis acceder au conteneur contenant l'application web 

```bash
  docker exec -it btsplay-laravel bash
```

- installer toutes les dépendances du projet

```bash
  composer install
  npm install
```

- Dans le conteneur toujours, initialiser la base de données

```bash
  php artisan migrate:fresh --seed
```

- Démarrer le serveur web

```bash
  npm run build
```
L'interface web est désormais accesible au http://localhost:8080, ou en remplaçant "localhost" par l'IP/le nom DNS de la machine sur le LAN



