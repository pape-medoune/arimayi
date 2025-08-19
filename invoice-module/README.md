# Module de Facturation Laravel

Un module complet de gestion de facturation développé avec Laravel, offrant une API REST pour la gestion des clients et des factures avec calculs automatiques.

## 🚀 Fonctionnalités

- **Gestion des clients** : CRUD complet avec validation
- **Gestion des factures** : Création, modification, suppression avec lignes de facture
- **Calculs automatiques** : TVA, totaux HT et TTC calculés automatiquement
- **API REST** : Endpoints complets avec validation des données
- **Architecture propre** : Services métier, Request classes, relations Eloquent
- **Validation robuste** : Règles métier et validation des données

## 📋 Prérequis

- PHP >= 8.1
- Composer
- MySQL >= 5.7 ou MariaDB >= 10.3
- Laravel >= 10.0

## 🛠️ Installation

1. **Cloner le projet**
```bash
git clone https://github.com/pape-medoune/arimayi.git
cd invoice-module
```

2. **Installer les dépendances**
```bash
composer install
```

3. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configuration de la base de données**
Modifiez le fichier `.env` avec vos paramètres de base de données :
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=invoice_module
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

5. **Exécuter les migrations**
```bash
php artisan migrate
```

6. **Lancer le serveur de développement**
```bash
php artisan serve
```

## 📊 Structure de la base de données

### Table `clients`
- `id` : Identifiant unique
- `nom` : Nom du client (requis)
- `email` : Adresse email (unique, requis)
- `siret` : Numéro SIRET (14 caractères, unique, optionnel)
- `date_creation` : Date de création du client
- `created_at`, `updated_at` : Timestamps Laravel

### Table `invoices`
- `id` : Identifiant unique
- `client_id` : Référence vers le client
- `numero_facture` : Numéro de facture (unique)
- `date_facture` : Date de la facture
- `total_ht` : Total hors taxes (calculé automatiquement)
- `total_tva` : Total TVA (calculé automatiquement)
- `total_ttc` : Total toutes taxes comprises (calculé automatiquement)
- `created_at`, `updated_at` : Timestamps Laravel

### Table `invoice_lines`
- `id` : Identifiant unique
- `invoice_id` : Référence vers la facture
- `description` : Description de la ligne
- `quantite` : Quantité
- `prix_unitaire_ht` : Prix unitaire hors taxes
- `taux_tva` : Taux de TVA (en pourcentage)
- `montant_ht` : Montant HT (calculé automatiquement)
- `montant_tva` : Montant TVA (calculé automatiquement)
- `montant_ttc` : Montant TTC (calculé automatiquement)
- `created_at`, `updated_at` : Timestamps Laravel

## 🔌 API Endpoints

### Clients

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/clients` | Liste tous les clients |
| POST | `/api/clients` | Crée un nouveau client |
| GET | `/api/clients/{id}` | Affiche un client spécifique |
| PUT/PATCH | `/api/clients/{id}` | Met à jour un client |
| DELETE | `/api/clients/{id}` | Supprime un client |
| GET | `/api/clients/{id}/invoices` | Factures d'un client |

### Factures

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/invoices` | Liste toutes les factures |
| POST | `/api/invoices` | Crée une nouvelle facture |
| GET | `/api/invoices/{id}` | Affiche une facture spécifique |
| PUT/PATCH | `/api/invoices/{id}` | Met à jour une facture |
| DELETE | `/api/invoices/{id}` | Supprime une facture |
| GET | `/api/invoices/stats` | Statistiques des factures |

### Utilitaires

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| GET | `/api/health` | Vérification de l'état de l'API |

## 📝 Exemples d'utilisation

### Créer un client
```bash
curl -X POST http://localhost:8000/api/clients \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "Entreprise ABC",
    "email": "contact@abc.com",
    "siret": "12345678901234",
    "date_creation": "2024-01-15"
  }'
```

### Créer une facture
```bash
curl -X POST http://localhost:8000/api/invoices \
  -H "Content-Type: application/json" \
  -d '{
    "client_id": 1,
    "date_facture": "2024-01-20",
    "lines": [
      {
        "description": "Prestation de développement",
        "quantite": 10,
        "prix_unitaire_ht": 500.00,
        "taux_tva": 20.00
      },
      {
        "description": "Formation",
        "quantite": 2,
        "prix_unitaire_ht": 800.00,
        "taux_tva": 20.00
      }
    ]
  }'
```

## 🏗️ Architecture

### Modèles Eloquent
- **Client** : Gestion des clients avec relation vers les factures
- **Invoice** : Gestion des factures avec calculs automatiques
- **InvoiceLine** : Lignes de facture avec calculs automatiques

### Services métier
- **ClientService** : Logique métier pour les clients
- **InvoiceService** : Logique métier pour les factures et statistiques

### Validation
- **StoreClientRequest** / **UpdateClientRequest** : Validation des clients
- **StoreInvoiceRequest** / **UpdateInvoiceRequest** : Validation des factures

### Contrôleurs API
- **ClientController** : Endpoints pour la gestion des clients
- **InvoiceController** : Endpoints pour la gestion des factures

## ⚙️ Calculs automatiques

Le système effectue automatiquement les calculs suivants :

### Au niveau des lignes de facture
- `montant_ht = quantite × prix_unitaire_ht`
- `montant_tva = montant_ht × (taux_tva / 100)`
- `montant_ttc = montant_ht + montant_tva`

### Au niveau des factures
- `total_ht = Σ(montant_ht des lignes)`
- `total_tva = Σ(montant_tva des lignes)`
- `total_ttc = Σ(montant_ttc des lignes)`

## 🧪 Tests

Pour exécuter les tests :
```bash
php artisan test
```

## 📚 Documentation API

La documentation complète de l'API est disponible via les endpoints suivants :
- Format de réponse standardisé avec `success`, `data`, `message`
- Gestion d'erreurs avec codes HTTP appropriés
- Validation automatique des données d'entrée

## 🤝 Contribution

1. Fork le projet
2. Créez votre branche de fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 👥 Auteurs

- Votre nom - Développeur principal

## 🆘 Support

Pour toute question ou problème, veuillez ouvrir une issue sur GitHub.
