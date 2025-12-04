# 1. Base image
FROM node:20-alpine

# 2. Set working directory
WORKDIR /app

# 3. Copy package files
COPY package.json ./

# 4. Install dependencies
RUN npm install --production

# 5. Copy the rest of the app
COPY . .

# 6. Expose port
EXPOSE 5000

# 7. Set environment variables
ENV NODE_ENV=production

# 8. Start the server
CMD ["node", "server.js"]
# CMD ["npm", "run", "start-dev"]  --- IGNORE ---