import express from "express";
import { 
    healthCheck, 
    detailedHealth, 
    testEndpoint, 
    testAuthEndpoint 
} from "../controllers/systemController.js";
import { authenticate } from "../config/auth.js";

const router = express.Router();

router.get("/", healthCheck);
router.get("/health", detailedHealth);
router.post("/test", testEndpoint);
router.post("/test-auth", authenticate, testAuthEndpoint);

export default router;