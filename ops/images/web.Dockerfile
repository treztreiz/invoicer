# syntax=docker/dockerfile:1

ARG NODE_VERSION
ARG NGINX_VERSION

# ---- base image ----
FROM node:${NODE_VERSION?} AS base
WORKDIR /app
COPY webapp/package*.json ./
RUN npm ci
COPY webapp/ .

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
RUN mkdir -p /etc/nginx/templates
COPY ops/nginx/nginx.base.conf /etc/nginx/templates/base.conf.tpl
COPY ops/nginx/nginx.prod.conf /etc/nginx/conf.d/webapp
COPY ops/nginx/nginx-entrypoint.sh /docker-entrypoint.d/00-envsubst.sh
COPY --from=build /app/dist /usr/share/nginx/html
