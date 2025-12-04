import express from "express";
import { 
    getLatestData, 
    getAllData, 
    getFileData, 
    searchData, 
    getDataSummary 
} from "../controllers/dataController.js";
import { authenticate } from "../config/auth.js";

const router = express.Router();

router.get("/:type", authenticate, getLatestData);
router.get("/:type/all", authenticate, getAllData);
router.get("/:type/file/:filename", authenticate, getFileData);
router.get("/:type/search", authenticate, searchData);
router.get("/:type/summary", authenticate, getDataSummary);

export default router;