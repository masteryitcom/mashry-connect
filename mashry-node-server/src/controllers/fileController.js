// src/controllers/fileController.js
import fs from "fs";
import path from "path";
import { readDataFiles } from "../utils/fileHelpers.js";

const DATA_DIR = "./data";

export const listFiles = (req, res) => {
    try {
        const type = req.query.type;
        
        console.log(`Listing files for type: ${type || 'all'}`);
        
        let files = [];
        
        if (type) {
            const typeDir = path.join(DATA_DIR, type);
            if (fs.existsSync(typeDir)) {
                const fileList = fs.readdirSync(typeDir);
                console.log(`   Found ${fileList.length} files in ${typeDir}`);
                
                files = fileList.map(file => {
                    const filePath = path.join(typeDir, file);
                    const stats = fs.statSync(filePath);
                    return {
                        type: type,
                        filename: file,
                        path: filePath,
                        size: stats.size,
                        modified: stats.mtime,
                        created: stats.ctime
                    };
                });
            } else {
                console.log(`   Directory ${typeDir} does not exist`);
                return res.json({
                    success: true,
                    count: 0,
                    files: [],
                    message: `No directory found for type: ${type}`
                });
            }
        } else {
            const types = ["users", "products", "categories"];
            
            types.forEach(t => {
                const typeDir = path.join(DATA_DIR, t);
                if (fs.existsSync(typeDir)) {
                    const fileList = fs.readdirSync(typeDir);
                    console.log(`   Found ${fileList.length} files in ${t} directory`);
                    
                    fileList.forEach(file => {
                        try {
                            const filePath = path.join(typeDir, file);
                            const stats = fs.statSync(filePath);
                            files.push({
                                type: t,
                                filename: file,
                                path: filePath,
                                size: stats.size,
                                modified: stats.mtime,
                                created: stats.ctime
                            });
                        } catch (error) {
                            console.error(`   Error processing file ${file}:`, error.message);
                        }
                    });
                } else {
                    console.log(`   Directory ${typeDir} does not exist`);
                }
            });
        }
        
        files.sort((a, b) => new Date(b.modified) - new Date(a.modified));
        
        console.log(`Returning ${files.length} files total`);
        
        res.json({
            success: true,
            count: files.length,
            files: files,
            timestamp: new Date().toISOString()
        });
        
    } catch (error) {
        console.error("Error listing files:", error);
        res.status(500).json({
            success: false,
            error: "Error listing files",
            message: error.message
        });
    }
};

export const downloadFile = (req, res) => {
    try {
        const { type, filename } = req.params;
        const filePath = path.join(DATA_DIR, type, filename);
        
        if (!fs.existsSync(filePath)) {
            return res.status(404).json({
                success: false,
                error: "File not found",
                type: type,
                filename: filename,
                path: filePath
            });
        }
        
        res.download(filePath, filename);
        
    } catch (error) {
        res.status(500).json({
            success: false,
            error: "Error downloading file",
            message: error.message
        });
    }
};

export const getStats = (req, res) => {
    try {
        const stats = {
            users: { count: 0, size: 0 },
            products: { count: 0, size: 0 },
            categories: { count: 0, size: 0 },
            total: { count: 0, size: 0 }
        };
        
        ["users", "products", "categories"].forEach(type => {
            const typeDir = path.join(DATA_DIR, type);
            if (fs.existsSync(typeDir)) {
                const files = fs.readdirSync(typeDir);
                stats[type].count = files.length;
                
                files.forEach(file => {
                    const filePath = path.join(typeDir, file);
                    stats[type].size += fs.statSync(filePath).size;
                });
                
                stats.total.count += stats[type].count;
                stats.total.size += stats[type].size;
            }
        });
        
        res.json({
            success: true,
            stats: stats,
            data_directory: DATA_DIR,
            server_uptime: process.uptime()
        });
        
    } catch (error) {
        res.status(500).json({
            success: false,
            error: "Error getting stats",
            message: error.message
        });
    }
};