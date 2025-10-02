FROM node:alpine3.18

RUN apk --no-cache add bash
WORKDIR /var/www/html

COPY ../../app/package.json .
RUN npm install 
COPY ../../app/ .

EXPOSE 3000
CMD ["npm", "start"]