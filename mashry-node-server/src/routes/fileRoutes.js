import express from "express";
import { 
    listFiles, 
    downloadFile, 
    getStats 
} from "../controllers/fileController.js";
import { authenticate } from "../config/auth.js";

const router = express.Router();

router.get("/", authenticate, listFiles);
router.get("/:type/:filename", authenticate, downloadFile);
router.get("/stats", authenticate, getStats);

export default router;