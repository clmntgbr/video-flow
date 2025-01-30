# Symfony Docker FrankenPHP Boilerplate

Based on A [Symfony Docker](https://github.com/dunglas/symfony-docker) installer and runtime for the [Symfony](https://symfony.com) web framework,
with [FrankenPHP](https://frankenphp.dev) and [Caddy](https://caddyserver.com/) inside!

## Getting Started

1. Run `make build` to build fresh images
2. Run `make start` to set up and start a fresh Symfony project
3. Open `https://localhost` in your favorite web browser
4. Run `make stop` to stop the Docker containers.

# TLS Certificates

## Trusting the Authority

With a standard installation, the authority used to sign certificates generated in the Caddy container is not trusted by your local machine.
You must add the authority to the trust store of the host :

```
# Mac
$ docker cp $(docker compose ps -q php):/data/caddy/pki/authorities/local/root.crt /tmp/root.crt && sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/root.crt
# Linux
$ docker cp $(docker compose ps -q php):/data/caddy/pki/authorities/local/root.crt /usr/local/share/ca-certificates/root.crt && sudo update-ca-certificates
# Windows
$ docker compose cp php:/data/caddy/pki/authorities/local/root.crt %TEMP%/root.crt && certutil -addstore -f "ROOT" %TEMP%/root.crt
```

## Features

* Blazing-fast performance thanks to [the worker mode of FrankenPHP](https://github.com/dunglas/frankenphp/blob/main/docs/worker.md) (automatically enabled in prod mode)
* PostGreSQL
* RabbitMQ
* ApiPlatform, Doctrine, Fixtures, AMQP Messenger bundles in symfony

**Enjoy!**

## License

Symfony Docker FrankenPHP is available under the MIT License.
