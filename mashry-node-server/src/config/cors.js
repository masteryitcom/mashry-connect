import cors from "cors";
import dotenv from "dotenv";

dotenv.config();

const allowedOrigins = process.env.ALLOWED_ORIGINS
  ? process.env.ALLOWED_ORIGINS.split(",")
  : ["*"];

const corsConfig = cors({
    origin: (origin, callback) => {
        if (!origin || allowedOrigins.includes(origin)) {
            callback(null, true);
        } else {
            callback(new Error(`Origin ${origin} not allowed by CORS`));
        }
    },
    credentials: process.env.ALLOW_CREDENTIALS === "true",
    methods: process.env.ALLOWED_METHODS.split(","),
    allowedHeaders: process.env.ALLOWED_HEADERS.split(","),
    exposedHeaders: process.env.EXPOSED_HEADERS?.split(",") || [],
    maxAge: parseInt(process.env.MAX_AGE) || 3600,
});

export default corsConfig;
