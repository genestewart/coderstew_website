# CoderStew Website

This repository contains the source code for the **CoderStew** freelance web development site. The project uses a Laravel backend with Vue 3 for the frontend and is designed to run inside Docker containers.

## Development Setup

1. Ensure you have [Docker](https://docs.docker.com/get-docker/) and Docker Compose installed.
2. Navigate to the `backend/` directory and install dependencies:

```bash
cd backend
composer install
npm install # if Node packages are needed

# Run backend tests from this directory
composer test
```

3. From the repository root, start the containers:

```bash
docker compose up -d
```

The application will be available at [http://localhost:8080](http://localhost:8080).

## Running Tests

PHPUnit tests can be executed inside the `app` container:

```bash
docker compose exec app php artisan test
```

## License

This project is released under the [MIT License](LICENSE).
