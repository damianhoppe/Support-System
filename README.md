# Support-System

A simple support system that supports viewing the most frequently asked questions.
Ultimately, the project is to offer a contact form and chat support for users (logged in and guests) with the help desk, along with multilingual support.

## Features
 - Browse frequently asked questions
 - Friendly links
 - Responsive UI
 - Light/Dark Mode
 - Login with OpenID Connect
 - Manage categories and questions from administrator account

## Setup
To run this project:
1. Requirements: PHP v8.1+, Database (supported by Doctrine), Symfony CLI
2. Clone repository
```
git clone https://github.com/damianhoppe/Support-System.git
```
3. Complete the environment variables(open .env for more details)
4. Run server
```
symfony server:start
```