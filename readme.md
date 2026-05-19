# 🌸 StoryLine ✒️ ![Status](https://img.shields.io/badge/status-ONGOING-brightgreen)

One sentence.

A thousand stories.

## Dashboard

<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/dashboard.jpg"/>
<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/dashboard-mobile.jpg"/>

### Story

<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/story.jpg"/>
<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/story-mobile.jpg"/>

### User profile

<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/profile.jpg"/>
<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/profile-mobile.jpg"/>

### Login

<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/login.jpg"/>
<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/login-mobile.jpg"/>

## Register

<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/register.jpg"/>
<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/register-mobile.jpg"/>

### Password reset

<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/password-forgot.jpg"/>
<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/password-forgot-mobile.jpg"/>

### Database ERD

<img src="https://github.com/fool-of-all-trades/StoryLine/blob/master/screenshots/erd.png"/>

## Docker deployment

`docker-compose.yml` is for local development and exposes PostgreSQL, pgAdmin, and MailHog on host ports.

For production, use the standalone production compose file so only nginx is published:

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

Set these values in the deployment environment or secret manager before starting production:

```bash
WEB_HTTP_PORT=80
POSTGRES_DB=...
POSTGRES_USER=...
POSTGRES_PASSWORD=...
APP_IP_SALT=...
PUBLIC_LAUNCH_DATE=2026-05-15
```

Do not combine the production compose file with the local development compose file.

## License

This project is protected under a custom Non-Commercial License.  
Unauthorized commercial use, redistribution, or modification of the code is prohibited.  
© 2025 [fool-of-all-trades]
