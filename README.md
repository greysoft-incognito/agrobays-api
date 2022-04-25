# AGROBAYS API

> Designed to bridge the Agrobase web/mobile and desktop interface

## Installation

### Environment Variables

Duplicate the .env.example file and modify the content to match your preferred deployment environment

### Run Composer

Run `composer install` to install all the project dependencies

### Migrations

Once all dependencies have been installed run `php artisan migrate` to run migrations

### Run Scheduled Task

Create a cron job with the following command to run all scheduled task: `* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1`

## API Documentation

[https://documenter.getpostman.com/view/19266444/UVsEVUx2](https://documenter.getpostman.com/view/19266444/UVsEVUx2 "API Documentation")
