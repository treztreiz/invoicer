# syntax=docker/dockerfile:1

ARG NODE_VERSION
ARG NGINX_VERSION

# ---- base image ----
FROM node:${NODE_VERSION?} AS base
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ .

# ---- Dev image ----
FROM base AS dev
ENV NODE_ENV=development
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]

# ---- Build image ----
FROM base AS build
ENV NODE_ENV=production
RUN npm run build

# ---- production image ----
FROM nginx:${NGINX_VERSION?} AS prod
COPY ops/conf.d/nginx.prod.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
