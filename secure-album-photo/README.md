# secg4-project-Secure-Album-Photo
G57618 - Nicolas Tassenoey

# Project Summary

## Context
The objective of this project was to develop a secure client/server system for storing photos and sharing albums or photos between authenticated users. 
Emphasis was placed on implementing robust security measures throughout the system, considering both security policy and data storage aspects. 
We were free to choose appropriate protocols, languages ​​and techniques. 
The project highlighted the importance of using effective security techniques, whether they were covered in class or not.

## Functionality
The system operates on a client/server architecture, involving two main actors: the server and the clients driven by users. 
The server provides several key functionalities, including user registration, user login, and the ability for authenticated users to edit and share albums or photos. 
The exact implementation details were left to our discretion, allowing us to identify critical security points and employ appropriate measures accordingly.

# Main functionalities

## User Registration and Authentication:
New users can register to the system, providing necessary information and generating authentication credentials (passwords, cryptographic keys, etc.).
User authentication is required to access the system's features, ensuring secure communication between clients and the server. Use the framework Breeze for laravel

## Adding, Deleting and Sharing Albums or photos:
The user can add or remove your personnal albums or photos.
He can share too an album containing photos or share a standalone photo to a other user.

## Photo upload:
The user can upload a encrypted photo with a symetric key and encrypt symetric key with his public key and store in the sotrage and DB.
He can also delete his photo

# Windows Requirements
[OpenSSL](https://www.openssl.org/source/)
<br>
[Xampp](https://www.apachefriends.org/fr/download.html)
<br>
[Composer](https://getcomposer.org/download/)
<br>
[Note.js](https://nodejs.org/en/download/)
<br>
[GitBash](https://git-scm.com/downloads)
<br>

# How setup project 
Intall my project in htdocs into a folder Xampp:
exemple path: "C:\xampp\htdocs\'clone_here'"
1. open GitBash and clone my repo with: "https://git.esi-bru.be/57618/final_project_secg4.git"
	a. Change de path in "C:\xampp\apache\conf\httpd) document_root
2. cd final_project_secg4/secure-album-photo
3. commposer install and npm install
4. delete all photos in "secure-album-photo\storage\app\private\photos"
5. reset all table: "php artisan migrate:fresh"
6. generate key for the server: "php artisan key:generate"

## Setup Xampp
Now, we will use and set up Xampp to run the project with SSL certificates, enabling HTTPS. To do this, follow these steps:

Place the "certif" folder in the `xampp/apache/conf` directory.

Add the following code to `xampp/apache/conf/extra/httpd-vhosts.conf`:

```
    <VirtualHost *:443>
        ServerName localhost
        DocumentRoot "/path/to/your/project/public"   
    
        SSLEngine on
        SSLCertificateFile "/path/to/your/certificat.crt"
        SSLCertificateKeyFile "/path/to/your/key_certificat.key"           	

    
        <Directory "/path/to/your/project/public">       
            Options Indexes FollowSymLinks
            AllowOverride All
            Require all granted
        </Directory>
    </VirtualHost>
```
Please make sure to replace `"/path/to/your/project/"` with the actual path to your project directory and `"path/to/your/certificat"` with the actual paths to your SSL certificate files.(in "`certif`" folder)

# Running the Project
in your project, start a bash and make: "npm run dev"
Open Xampp and start the Apache server and MySQL.
Now, open a web browser and navigate to `https://localhost`.
!! IMPORTANT: Each user receives a key pair at their first login, they are stored in your browser's localstorage. To test on multiple users, open another browser or a private session :IMPORTANT !!

# Navigation

# Home page 
Arrived at this page you can connect 

## Login/Registration
To create an account you need to fill the fields.<br>
To login, you just need to put your email and password.
NB: it should be noted that these passwords are not at all secure and that you must have a strong password.

## DashBoard
Redirect after to create a user. He generate the pair of key for the user and store in LocalStorage of your navigator.
Redirect instantly to the albums

## Album
The authenticated user can create, delete and view his albums, as well as standalone photos.
He can also view albums and photos that have been shared with him.

## Album/{{idAlbum}}
The authenticated user who has the rights (owner or shared) can see the photos.
The owner can add or remove sharing with users, the same for photos.
