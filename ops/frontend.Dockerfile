# syntax=docker/dockerfile:1

FROM node:20-alpine AS base
WORKDIR /app
COPY frontend/package*.json ./
RUN npm ci
COPY frontend/ .

FROM base AS dev
ENV NODE_ENV=development
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0", "--port", "5173"]

FROM base AS build
ENV NODE_ENV=production
RUN npm run build

FROM nginx:alpine AS prod
COPY ops/conf.d/nginx.prod.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html