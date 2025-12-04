import express from "express";
import { importData } from "../controllers/importController.js";
import { authenticate } from "../config/auth.js";

const router = express.Router();

router.post("/:type", authenticate, importData);

export default router;