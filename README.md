# Secure-Album
Album d'images sécurisé

## Images
- image création de compte:
  
![alt text](https://github.com/tazznico/Secure-Album/blob/main/images/register_page.JPG)

- image du stockage des clés:

![alt text](https://github.com/tazznico/Secure-Album/blob/main/images/my_secure_key.JPG)

- image d'un album:

![alt text](https://github.com/tazznico/Secure-Album/blob/main/images/album_show_page.JPG)


- image d'un album partagé:

![alt text](https://github.com/tazznico/Secure-Album/blob/main/images/album_sharing_page.JPG)

- image de l'index des albums:

![alt text](https://github.com/tazznico/Secure-Album/blob/main/images/album_index_page.JPG)

## Description
Projet de deuxième année visant à mettre en place la sécurité d'un album pouvant être partagé entre différents utilisateur.
la mise d'une signature a été implémentée dans un projet similaire.

## Lancer le projet
```console
cd .\Secure-Album\secure-album-photo\
composer install
npm install
cp .\.env.example .env
php artisan key:generate
php artisan migrate
```
```console
php artisan serve
```
```console
npm run dev
```
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
	1. Récupération de la clé publique de l'utilisateur à qui on partage l'album / photo
	2. décrytage de la clé symétrique avec la clé privée du propriétaire (action faisable que par le propriétaire)
	3. encryptage de la clé symétrique de l'album / photo avec la clé publique de l'utilisateur à qui l'on partage l'album / photo

- SSH
	1. Utiliser le dossier certif 
