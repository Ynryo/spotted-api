<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.4" />
  <img src="https://img.shields.io/badge/Symfony-7.4-000000?style=for-the-badge&logo=symfony&logoColor=white" alt="Symfony 7.4" />
  <img src="https://img.shields.io/badge/Server-FrankenPHP-00ADD8?style=for-the-badge&logo=caddy&logoColor=white" alt="FrankenPHP" />
  <img src="https://img.shields.io/badge/Cache-Redis_7-DC382D?style=for-the-badge&logo=redis&logoColor=white" alt="Redis 7" />
  <img src="https://img.shields.io/badge/OpenAPI-Swagger_UI-85EA2D?style=for-the-badge&logo=swagger&logoColor=black" alt="Swagger UI" />
  <img src="https://img.shields.io/badge/License-MIT-blue.svg?style=for-the-badge" alt="License MIT" />
</p>

Une API REST moderne, performante et hautement disponible pour alimenter l'application **[Spotted](https://github.com/Ynryo/spotted)**. Elle agrège, normalise et met en cache en temps réel les données de position, de trajets, d'arrêts et de matériel roulant des transports en commun français (bus, trams, métros, TER, TGV).

---

### ⚡ Cache Ultra-Rapide & Worker Dédié
- **Cache mémoire Redis** : Mise en cache agressive avec durées de vie adaptées (`CacheTtl`) pour garantir des temps de réponse sous les 10 ms.
- **Worker d'arrière-plan autonome** : Commande `app:sync-markers --watch` tournant en continu dans un conteneur dédié pour pré-charger et rafraîchir en temps réel les milliers de marqueurs de circulation.
- **Support de la compression Gzip** : Décompression transparente des flux lourds pour réduire l'empreinte réseau et CPU.

### 📍 Données Véhicules & Géométrie en Temps Réel
- **Marqueurs dynamiques & Bounding Box** : Filtrage spatial optimisé par coordonnées géographiques (`swLat`, `swLon`, `neLat`, `neLon`) pour ne renvoyer que les véhicules visibles à l'écran.
- **Tracés vectoriels des lignes** : Distribution des tracés géométriques complets (`Path`) des parcours empruntés.
- **Fiches véhicules enrichies** : Type de véhicule, numéro de parc, modèle commercial, présence de climatisation ou de ports USB.

### 🚆 Support Ferroviaire Avancé & Unités Multiples (UM)
- **Détection automatique des rames accouplées (UM)** : Rapprochement intelligent et fusion des circulations de trains accouplés pour une timeline d'arrêts synchronisée.
- **Estimation prédictive des voies** : Interrogation intelligente pour estimer et anticiper les quais de départ/arrivée avant l'affichage officiel en gare.

### 📚 Documentation Interactive Intégrée
- **Swagger / OpenAPI intégré** : Documentation interactive générée avec NelmioApiDocBundle accessible immédiatement sur l'interface Swagger UI.

---

## 🏗️ Architecture & Composants

```
src/
├── Command/
│   └── SyncMarkersCommand.php       # Worker CLI de synchronisation continue des marqueurs dans Redis
│
├── Controller/                      # Points d'entrée REST & annotations OpenAPI
│   ├── JourneyController.php        # Détails de parcours, arrêts, retards et composition
│   ├── MarkerController.php         # Marqueurs temps réel avec filtrage géographique (BBox)
│   ├── NetworkController.php        # Réseaux de transport et recherche de lignes
│   ├── PathController.php           # Tracés géométriques vectoriels des parcours
│   ├── RegionController.php         # Découpage administratif et réseaux régionaux
│   └── VehicleController.php        # Données de flotte et détails de véhicules
│
├── DTO/                             # Objets de transfert et validation des requêtes (Validator)
│   └── MarkerQueryDto.php           # Validation des coordonnées BBox et filtres
│
├── Enum/                            # Enums typés natifs PHP 8.1+
│   ├── AirConditioning.php          # Statut climatisation (YES, NO, UNKNOWN)
│   ├── CacheTtl.php                 # Centralisation des durées d'expiration Redis
│   ├── Occupancy.php                # Niveau de charge à bord
│   ├── StopType.php                 # Type d'arrêt (COMMERCIAL, TECHNICAL, PASSING...)
│   ├── TrackingType.php             # Type de géolocalisation (REALTIME, ESTIMATED, STATIC)
│   └── VehicleType.php              # Catégorie (BUS, TRAM, TRAIN, SUBWAY...)
│
├── Model/                           # Modèles métiers découplés et immuables
│   ├── Journey/                     # Détails de trajets, arrêts et plateformes
│   ├── Network/                     # Réseaux de transport et lignes
│   ├── Path/                        # Géométrie et coordonnées des segments
│   ├── Region/                      # Découpage territorial
│   └── Vehicle/                     # Caractéristiques physiques du matériel roulant
│
└── Service/                         # Logique métier et intégrations externes
    ├── BusTrackerClient.php         # Client HTTP vers l'API de données de transport
    ├── CartoTchooClient.php         # Client HTTP d'estimation des voies ferroviaires
    ├── JourneyDetailsService.php    # Assemblage des trajets et enrichissement
    ├── PlatformService.php          # Gestion et résolution des quais d'arrêts
    ├── TrainUMService.php           # Algorithme de fusion des trains en Unité Multiple
    └── VehicleService.php           # Gestion et enrichissement des informations véhicules
```

---

## 🛠️ Stack Technique

| Domaine | Technologie / Outil | Utilisation |
|:---|:---|:---|
| **Langage** | PHP 8.4 | Typage strict, constructeurs promus, readonly classes & enums |
| **Framework** | Symfony 7.4 | Framework HTTP léger sans Doctrine ORM |
| **Serveur d'application** | FrankenPHP (Caddy) | Serveur d'application moderne en conteneur Alpine |
| **Cache & In-Memory** | Redis 7 (Alpine) | Persistance ultra-rapide des marqueurs et données d'arrêts |
| **Documentation** | NelmioApiDocBundle | Spécification OpenAPI v3 & interface Swagger UI |
| **Analyse Statique** | PHPStan (Niveau configuré) | Vérification rigoureuse des types et de la robustesse |
| **Tests Unitaires** | PHPUnit 12 | 50+ tests automatisés couvrant les modèles, DTOs et services |

---

## 🚀 Démarrage Rapide

### 1. Prérequis
- [Docker](https://www.docker.com/) & [Docker Compose](https://docs.docker.com/compose/)
- *(Optionnel en local)* : PHP 8.4+ et [Composer](https://getcomposer.org/)

### 2. Cloner le projet
```bash
git clone https://github.com/Ynryo/spotted-api.git
cd spotted-api
```

### 3. Variables d'environnement
Créez un fichier `.env.local` si vous souhaitez surcharger la configuration par défaut :
```bash
cp .env .env.local
```

### 4. Lancer avec Docker
Démarrez l'ensemble de la stack (Serveur PHP FrankenPHP, instance Redis, et Worker de synchronisation) :
```bash
docker compose up -d --build
```

L'API est immédiatement accessible sur `http://localhost:8080`.

---

## 📖 Documentation de l'API (Swagger UI)

Une interface interactive Swagger UI est disponible directement à la racine ou sur `/api/doc` :
```text
http://localhost:8080/api/doc
```

Vous pouvez y tester l'ensemble des endpoints :
- `GET /api/markers` : Récupération des marqueurs temps réel sur une zone géographique donnée.
- `GET /api/vehicle-journeys/{id}` : Fiche détaillée d'un trajet avec arrêts restants et retards.
- `GET /api/paths/{id}` : Tracé géométrique vectoriel du parcours.
- `GET /api/networks` : Liste des réseaux de transport urbains et régionaux.
- `GET /api/regions` : Découpage territorial par régions.

---

## 🧪 Tests & Qualité de Code

Exécutez la suite de tests unitaires et l'analyse statique :

```bash
# Tests unitaires PHPUnit
vendor/bin/phpunit

# Analyse statique PHPStan
vendor/bin/phpstan analyse
```

---

## 🤝 Remerciements & Sources de Données

- **[Kevin Biojout](https://github.com/kevinbioj)** pour le projet open source [bus-tracker-2](https://github.com/kevinbioj/bus-tracker-2) qui agrège les flux GTFS/GTFS-RT des réseaux de transport.
- **[Nicolas Wurtz](https://github.com/nicolaswurtz)** pour [Carto Tchoo](https://carto.tchoo.net) et son travail sur la prédiction des voies ferroviaires.
- Les plateformes ouvertes [transport.data.gouv.fr](https://transport.data.gouv.fr) et [data.gouv.fr](https://www.data.gouv.fr).

---

## 📄 Licence

Ce projet est distribué sous licence **MIT**. Consultez le fichier [LICENSE](LICENSE) pour plus de détails.
