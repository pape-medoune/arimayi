# 🚀 Guide de Déploiement - Documentation Swagger sur GitHub Pages

## 📋 Prérequis

- Repository Git existant sur GitHub
- Accès aux paramètres du repository
- Documentation Swagger générée localement

## 🔧 Configuration GitHub Pages

### Étape 1 : Activer GitHub Pages

1. **Accédez aux paramètres du repository** :
   - Allez sur votre repository GitHub
   - Cliquez sur l'onglet **Settings**

2. **Configurez GitHub Pages** :
   - Dans le menu latéral, cliquez sur **Pages**
   - Dans **Source**, sélectionnez **GitHub Actions**
   - Sauvegardez les modifications

### Étape 2 : Pousser les fichiers

```bash
# Ajouter tous les nouveaux fichiers
git add .

# Commit avec un message descriptif
git commit -m "feat: Add Swagger documentation deployment setup

- Add GitHub Pages configuration
- Add GitHub Actions workflow for auto-deployment
- Add static HTML page for Swagger UI
- Add documentation files and README"

# Pousser vers GitHub
git push origin main
```

### Étape 3 : Vérifier le Déploiement

1. **Vérifiez le workflow** :
   - Allez dans l'onglet **Actions** de votre repository
   - Vérifiez que le workflow "Deploy Swagger Documentation" s'exécute
   - Attendez que le statut passe à ✅ (succès)

2. **Accédez à la documentation** :
   - URL : `https://[votre-username].github.io/[nom-du-repo]/`
   - Remplacez `[votre-username]` et `[nom-du-repo]` par vos valeurs

## 📁 Structure des Fichiers Créés

```
├── .github/
│   └── workflows/
│       └── deploy-docs.yml          # Workflow GitHub Actions
├── docs/
│   ├── index.html                   # Interface Swagger UI
│   ├── api-docs.json               # Documentation OpenAPI (JSON)
│   ├── _config.yml                 # Configuration GitHub Pages
│   └── README.md                   # Documentation du dossier
└── DEPLOYMENT_GUIDE.md             # Ce guide
```

## 🔄 Processus de Déploiement Automatique

### Déclencheurs
- Push sur la branche `main` ou `master`
- Pull Request vers ces branches
- Déclenchement manuel via l'interface GitHub

### Étapes du Workflow
1. **Checkout** : Récupération du code source
2. **Setup PHP** : Installation de PHP 8.2 et extensions
3. **Dependencies** : Installation des dépendances Composer
4. **Configuration** : Copie de .env et génération de clé
5. **Tests** : Exécution des tests PHPUnit
6. **Documentation** : Génération de la documentation Swagger
7. **Préparation** : Copie des fichiers vers le dossier docs
8. **Déploiement** : Publication sur GitHub Pages

## 🛠️ Personnalisation

### Modifier l'Apparence

Éditez `docs/index.html` pour personnaliser :
- Couleurs et styles CSS
- Titre et description
- Configuration Swagger UI

### Ajouter des Informations

Mettez à jour `docs/_config.yml` avec :
- Votre nom d'utilisateur GitHub
- Le nom de votre repository
- Vos informations de contact

### Configuration Avancée

Modifiez `.github/workflows/deploy-docs.yml` pour :
- Changer la version PHP
- Ajouter des étapes de build
- Modifier les conditions de déclenchement

## 🔍 Dépannage

### Problèmes Courants

1. **Workflow échoue** :
   - Vérifiez les logs dans l'onglet Actions
   - Assurez-vous que les tests passent localement
   - Vérifiez les permissions du repository

2. **Page 404** :
   - Attendez quelques minutes après le déploiement
   - Vérifiez l'URL (username et nom du repo)
   - Assurez-vous que GitHub Pages est activé

3. **Documentation vide** :
   - Vérifiez que `api-docs.json` existe
   - Régénérez la documentation : `php artisan l5-swagger:generate`
   - Vérifiez les annotations Swagger dans vos contrôleurs

### Commandes Utiles

```bash
# Régénérer la documentation localement
php artisan l5-swagger:generate

# Copier les fichiers mis à jour
cp storage/api-docs/api-docs.json docs/
cp storage/api-docs/api-docs.yaml docs/

# Tester localement
cd docs && python -m http.server 8080
```

## 📞 Support

En cas de problème :
1. Consultez les logs GitHub Actions
2. Vérifiez la documentation Laravel L5-Swagger
3. Consultez la documentation GitHub Pages

## 🎉 Félicitations !

Votre documentation Swagger est maintenant déployée et accessible publiquement ! 🚀