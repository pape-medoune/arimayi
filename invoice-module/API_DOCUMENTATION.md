# Documentation API - Module de Facturation

## Format de réponse standardisé

Toutes les réponses de l'API suivent le format JSON suivant :

### Réponse de succès
```json
{
  "success": true,
  "data": { /* données de la réponse */ },
  "message": "Message de succès"
}
```

### Réponse d'erreur
```json
{
  "success": false,
  "message": "Message d'erreur",
  "error": "Détails de l'erreur (optionnel)",
  "errors": { /* erreurs de validation (optionnel) */ }
}
```

## Endpoints Clients

### GET /api/clients
Récupère la liste de tous les clients.

**Réponse :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "nom": "Entreprise ABC",
      "email": "contact@abc.com",
      "siret": "12345678901234",
      "date_creation": "2024-01-15",
      "created_at": "2024-01-15T10:00:00.000000Z",
      "updated_at": "2024-01-15T10:00:00.000000Z"
    }
  ],
  "message": "Clients récupérés avec succès"
}
```

### POST /api/clients
Crée un nouveau client.

**Corps de la requête :**
```json
{
  "nom": "Entreprise ABC",
  "email": "contact@abc.com",
  "siret": "12345678901234",
  "date_creation": "2024-01-15"
}
```

**Validation :**
- `nom` : requis, chaîne, max 255 caractères
- `email` : requis, email valide, unique
- `siret` : optionnel, chaîne de 14 caractères, unique
- `date_creation` : optionnel, date valide

**Réponse (201) :**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nom": "Entreprise ABC",
    "email": "contact@abc.com",
    "siret": "12345678901234",
    "date_creation": "2024-01-15",
    "created_at": "2024-01-15T10:00:00.000000Z",
    "updated_at": "2024-01-15T10:00:00.000000Z"
  },
  "message": "Client créé avec succès"
}
```

### GET /api/clients/{id}
Récupère un client spécifique.

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "nom": "Entreprise ABC",
    "email": "contact@abc.com",
    "siret": "12345678901234",
    "date_creation": "2024-01-15",
    "created_at": "2024-01-15T10:00:00.000000Z",
    "updated_at": "2024-01-15T10:00:00.000000Z"
  },
  "message": "Client récupéré avec succès"
}
```

**Réponse d'erreur (404) :**
```json
{
  "success": false,
  "message": "Client non trouvé"
}
```

### PUT/PATCH /api/clients/{id}
Met à jour un client existant.

**Corps de la requête :**
```json
{
  "nom": "Entreprise ABC Modifiée",
  "email": "nouveau@abc.com"
}
```

**Validation :**
- `nom` : optionnel, chaîne, max 255 caractères
- `email` : optionnel, email valide, unique (excluant l'ID actuel)
- `siret` : optionnel, chaîne de 14 caractères, unique (excluant l'ID actuel)
- `date_creation` : optionnel, date valide

### DELETE /api/clients/{id}
Supprime un client.

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Client supprimé avec succès"
}
```

**Réponse d'erreur (400) - Client avec factures :**
```json
{
  "success": false,
  "message": "Impossible de supprimer le client car il a des factures associées"
}
```

### GET /api/clients/{id}/invoices
Récupère les factures d'un client spécifique.

**Réponse (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "numero_facture": "FAC-2024-0001",
      "date_facture": "2024-01-20",
      "total_ht": "6600.00",
      "total_tva": "1320.00",
      "total_ttc": "7920.00",
      "created_at": "2024-01-20T10:00:00.000000Z",
      "updated_at": "2024-01-20T10:00:00.000000Z"
    }
  ],
  "message": "Factures du client récupérées avec succès"
}
```

## Endpoints Factures

### GET /api/invoices
Récupère la liste de toutes les factures avec leurs clients.

**Réponse (200) :**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "client_id": 1,
      "numero_facture": "FAC-2024-0001",
      "date_facture": "2024-01-20",
      "total_ht": "6600.00",
      "total_tva": "1320.00",
      "total_ttc": "7920.00",
      "created_at": "2024-01-20T10:00:00.000000Z",
      "updated_at": "2024-01-20T10:00:00.000000Z",
      "client": {
        "id": 1,
        "nom": "Entreprise ABC",
        "email": "contact@abc.com"
      }
    }
  ],
  "message": "Factures récupérées avec succès"
}
```

### POST /api/invoices
Crée une nouvelle facture avec ses lignes.

**Corps de la requête :**
```json
{
  "client_id": 1,
  "numero_facture": "FAC-2024-0001",
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
}
```

**Validation :**
- `client_id` : requis, doit exister dans la table clients
- `numero_facture` : optionnel, chaîne max 50 caractères, unique (généré automatiquement si non fourni)
- `date_facture` : requis, date valide
- `lines` : requis, tableau avec au moins 1 élément
- `lines.*.description` : requis, chaîne max 500 caractères
- `lines.*.quantite` : requis, numérique, minimum 0.01
- `lines.*.prix_unitaire_ht` : requis, numérique, minimum 0
- `lines.*.taux_tva` : requis, numérique, entre 0 et 100

**Réponse (201) :**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "client_id": 1,
    "numero_facture": "FAC-2024-0001",
    "date_facture": "2024-01-20",
    "total_ht": "6600.00",
    "total_tva": "1320.00",
    "total_ttc": "7920.00",
    "created_at": "2024-01-20T10:00:00.000000Z",
    "updated_at": "2024-01-20T10:00:00.000000Z",
    "lines": [
      {
        "id": 1,
        "invoice_id": 1,
        "description": "Prestation de développement",
        "quantite": "10.00",
        "prix_unitaire_ht": "500.00",
        "taux_tva": "20.00",
        "montant_ht": "5000.00",
        "montant_tva": "1000.00",
        "montant_ttc": "6000.00",
        "created_at": "2024-01-20T10:00:00.000000Z",
        "updated_at": "2024-01-20T10:00:00.000000Z"
      },
      {
        "id": 2,
        "invoice_id": 1,
        "description": "Formation",
        "quantite": "2.00",
        "prix_unitaire_ht": "800.00",
        "taux_tva": "20.00",
        "montant_ht": "1600.00",
        "montant_tva": "320.00",
        "montant_ttc": "1920.00",
        "created_at": "2024-01-20T10:00:00.000000Z",
        "updated_at": "2024-01-20T10:00:00.000000Z"
      }
    ],
    "client": {
      "id": 1,
      "nom": "Entreprise ABC",
      "email": "contact@abc.com"
    }
  },
  "message": "Facture créée avec succès"
}
```

### GET /api/invoices/{id}
Récupère une facture spécifique avec ses lignes et son client.

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "client_id": 1,
    "numero_facture": "FAC-2024-0001",
    "date_facture": "2024-01-20",
    "total_ht": "6600.00",
    "total_tva": "1320.00",
    "total_ttc": "7920.00",
    "created_at": "2024-01-20T10:00:00.000000Z",
    "updated_at": "2024-01-20T10:00:00.000000Z",
    "lines": [ /* lignes de facture */ ],
    "client": { /* informations du client */ }
  },
  "message": "Facture récupérée avec succès"
}
```

### PUT/PATCH /api/invoices/{id}
Met à jour une facture existante.

**Corps de la requête :**
```json
{
  "date_facture": "2024-01-25",
  "lines": [
    {
      "description": "Prestation de développement modifiée",
      "quantite": 12,
      "prix_unitaire_ht": 550.00,
      "taux_tva": 20.00
    }
  ]
}
```

### DELETE /api/invoices/{id}
Supprime une facture et ses lignes.

**Réponse (200) :**
```json
{
  "success": true,
  "message": "Facture supprimée avec succès"
}
```

### GET /api/invoices/stats
Récupère les statistiques des factures.

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "total_invoices": 25,
    "total_amount_ht": "125000.00",
    "total_amount_tva": "25000.00",
    "total_amount_ttc": "150000.00",
    "average_amount_ttc": "6000.00",
    "invoices_this_month": 8,
    "amount_this_month": "48000.00"
  },
  "message": "Statistiques des factures récupérées avec succès"
}
```

## Endpoint Utilitaire

### GET /api/health
Vérifie l'état de l'API.

**Réponse (200) :**
```json
{
  "success": true,
  "data": {
    "status": "OK",
    "timestamp": "2024-01-20T10:00:00.000000Z",
    "version": "1.0.0"
  },
  "message": "API opérationnelle"
}
```

## Codes de statut HTTP

- **200 OK** : Requête réussie
- **201 Created** : Ressource créée avec succès
- **400 Bad Request** : Erreur dans la requête
- **404 Not Found** : Ressource non trouvée
- **422 Unprocessable Entity** : Erreur de validation
- **500 Internal Server Error** : Erreur serveur

## Gestion des erreurs

### Erreur de validation (422)
```json
{
  "success": false,
  "message": "Erreur de validation",
  "errors": {
    "email": ["Cette adresse email est déjà utilisée."],
    "siret": ["Le SIRET doit contenir exactement 14 caractères."]
  }
}
```

### Erreur de ressource non trouvée (404)
```json
{
  "success": false,
  "message": "Client non trouvé"
}
```

### Erreur serveur (500)
```json
{
  "success": false,
  "message": "Erreur lors de la création du client",
  "error": "Détails techniques de l'erreur"
}
```