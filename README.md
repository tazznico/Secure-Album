# Secure-Album
Album d'images sécurisé

## Images
- image création de compte:
  
![alt text]()

- image du stockage des clés:

![alt text]()

- image de l'acceuil:

![alt text]()


- image de son album photo:

![alt text]()

- image du partage d'album photo:

![alt text]()

## Description
Projet de deuxième année visant à mettre en place la sécurité d'un album pouvant être partagé entre différents utilisateur.
la mise d'une signature a été implémentée dans un projet similaire.

## Lancer le projet
!! cd .\Secure-Album\secure-album-photo\ !!

1. composer install
2. npm install
3. cp .\.env.example .env
4. php artisan key:generate
5. php artisan migrate (accepter la création de la db avec SQlite)
6. php artisan serve
7. npm run dev

## la sécurité
- Lors de la création du compte.
1. Création de la pair de clés privée et publique de l'utilisateur (génération = RSA-OAEP et hash = SHA-256)
	- la clé privée est stockée dans le local storage
	- la clé publique est stockée dans le local storage et dans le serveur

- Lors de la création d'un album / upload photo
1. Création d'une clé symétrique pour sécurisé les données de l'album / photo
2. Encrypter les données de l'album / photo avec la clé symétrique
3. encrypter la clé symétrique avec la clé publique du propriéttaire

- Lors du partage d'un album / photo
2. Récupération de la clé publique de l'utilisateur à qui on partage l'album / photo
3. décrytage de la clé symétrique avec la clé privée du propriétaire (action faisable que par le propriétaire)
4. encryptage de la clé symétrique de l'album / photo avec la clé publique de l'utilisateur à qui l'on partage l'album / photo

- SSH
1. Utiliser le dossier certif 