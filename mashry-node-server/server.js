// server.js
import express from "express";
import dotenv from "dotenv";
import path from "path";

// Import configurations
import corsConfig from "./src/config/cors.js";
import { authenticate } from "./src/config/auth.js";

// Import middleware
import { requestLogger } from "./src/middleware/logging.js";
import errorHandler from "./src/middleware/errorHandler.js";

// Import routes
import systemRoutes from "./src/routes/systemRoutes.js";
import importRoutes from "./src/routes/importRoutes.js";
import fileRoutes from "./src/routes/fileRoutes.js";
import dataRoutes from "./src/routes/dataRoutes.js";

dotenv.config();

const app = express();
const PORT = process.env.PORT || 5000;
const DATA_DIR = "./data";

// Create data directory if it doesn't exist
import fs from "fs";
if (!fs.existsSync(DATA_DIR)) {
    fs.mkdirSync(DATA_DIR, { recursive: true });
    console.log(`Created data directory: ${DATA_DIR}`);
}

// Middleware
app.use(corsConfig);
app.use(express.json({ limit: "50mb" }));
app.use(express.urlencoded({ extended: true, limit: "50mb" }));
app.use(requestLogger);

// Routes
app.use("/", systemRoutes);
app.use("/import", importRoutes);
app.use("/files", fileRoutes);
app.use("/data", dataRoutes);

// Error handling
app.use(errorHandler);

// Start server
app.listen(PORT, "0.0.0.0", () => {
    console.log("=".repeat(50));
    console.log("MARFOOF DATA RECEIVER");
    console.log("=".repeat(50));
    console.log(`Server running at: http://0.0.0.0:${PORT}`);
    console.log(`Data directory: ${path.resolve(DATA_DIR)}`);
    console.log(`API Key: ${process.env.API_KEY ? '***' : 'not set' }`);
    console.log("=".repeat(50));
});